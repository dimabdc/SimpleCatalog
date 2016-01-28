<?php

namespace console\controllers;


use dimabdc\simplehtmldom\SimpleHtmlDom;
use Yii;
use yii\console\Controller;
use yii\db\Query;
use yii\httpclient\Client;

class ParserController extends Controller
{

    private $_url = 'http://e-dostavka.by/';

    public $categoriesTable = 'categories';
    public $productsTable = 'products';

    const MASK_MAIN_CATEGORY = '/gg_clone_([0-9]+)/';
    const MASK_CATEGORY = '/catalog\/([0-9]+)/';



    public function actionParse()
    {
        $categories = $this->_getCategories();

        $savedCats = [];
        foreach ($categories as $cat) {
            $cat['parent_id'] = (isset($cat['parent_id']) && $cat['parent_id'] != 0) ? $savedCats[$cat['parent_id']]['id'] : 0;
            $id = $this->_addCategory($cat);
            $savedCats[$cat['id']] = [
                'id' => $id,
                'title' => $cat['title'],
            ];

            if (!isset($cat['url'])) {
                continue;
            }
            $this->add($this->_parseCategory($cat['url'], $id));

        }
    }



    private function _getCategories()
    {
        try {
            $html = SimpleHtmlDom::str_get_html(self::request($this->_url));
        } catch (\Exception $e) {
            return false;
        }

        $htmlCats = $html->find('li#catalog_menu_wrapper ul.catalog_menu', 0)->children();

        $categories = [];

        foreach ($htmlCats as $category)
        {
            if (!preg_match(self::MASK_MAIN_CATEGORY, $category->class, $matches)) {
                continue;
            }
            $catId = $matches[1];
            $categories[] = [
                'id' => (int) $catId,
                'parent_id' => 0,
                'title' => $category->children(0)->plaintext
            ];
            foreach ($category->children(1)->children(0)->children() as $category2)
            {
                if (!preg_match(self::MASK_MAIN_CATEGORY, $category2->class, $matches)) {
                    continue;
                }
                $catId2 = $matches[1];
                $categories[] = [
                    'id' => (int) $catId2,
                    'parent_id' => (int) $catId,
                    'title' => $category2->children(0)->plaintext
                ];
                foreach ($category2->children(1)->children() as $category3)
                {
                    if (!preg_match(self::MASK_CATEGORY, $category3->children(0)->href, $matches)) {
                        continue;
                    }
                    $categories[] = [
                        'id' => (int) $matches[1],
                        'parent_id' => (int) $catId2,
                        'title' => $category3->children(0)->plaintext,
                        'url' => $category3->children(0)->href
                    ];

                }
            }
        }

        return $categories;
    }



    private function _parseCategory($url, $category)
    {
        $products = [];

        try {
            $html = SimpleHtmlDom::str_get_html(self::request($url . '?ajax=1&page=1'));
        } catch (\Exception $e) {
            return $products;
        }

        foreach ($html->find('div.products_card') as $item) {
            //$item_url = $item->find('a.fancy_ajax', 1)->url;
            $title = $item->find('a.fancy_ajax', 0)->plaintext;
            $article = $item->find('input[name=product_id]', 0)->value;

            $unitCountry = $item->find('div.small_country', 0);
            $product_country = $unitCountry ? $unitCountry->plaintext : null;

            $unitItem = $item->find('div.form_elements input', 0);
            $unit = $unitItem ? $unitItem->getAttribute('data-measure') : 'шт';

            $unitPrice = $item->find('div.price', 0);
            if ($unitPrice->find('div', 0)) {
                $unitPrice->find('div', 0)->innertext = '';
            }
            $price = (int)str_replace(' ', '', $unitPrice->plaintext);
            $image = $item->find('img', 0)->src;

            $res = preg_match('/([\w\W]+) ([\d]+) (?=(г|кг|шт))|([\w\W]+)/ui', str_replace('&nbsp;', ' ', $title), $matches);

            if ($res) {
                $products[] = [
                    'category_id' => $category,
                    'title' => $matches[1] ? $matches[1] : $matches[4],
                    'price' => $price,
                    'mass' => ($matches[1] ? ($matches[3] === 'кг' ? $matches[2]*1000 : $matches[2]) : null),
                    'article' => $article,
                    'country' => $product_country,
                    'image' => $image,
                    'unit' => $unit
                ];
            } else {
                Yii::error("[Parser] e-dostavka.by $url $article");
            }
        }

        return $products;
    }



    public function add($data)
    {
        if (!$data) {
            return;
        }

        foreach ($data as $model)
        {
            $this->_prepareAndAdd($model);
        }
    }



    private function _addCategory($model)
    {
        $query = new Query();
        $id = $query->select('id')
            ->from($this->categoriesTable)
            ->where(['title' => $model['title']])
            ->scalar();

        if (!$id) {
            Yii::$app->db->createCommand()->insert($this->categoriesTable, [
                'parent_id' => $model['parent_id'],
                'title' => $model['title'],
            ])->execute();
            $id = Yii::$app->db->lastInsertID;
        }

        return $id;
    }



    private function _prepareAndAdd($model)
    {
        $query = new Query();
        $id = $query->select('id')
            ->from($this->productsTable)
            ->where(['title' => $model['title'], 'category_id' => $model['category_id']])
            ->scalar();

        if (!$id)
        {
            Yii::$app->db->createCommand()->insert($this->productsTable, $model)->execute();
            $id = Yii::$app->db->getLastInsertID();
        }

        return $id;
    }



    public static function request($url, $params = array(), $requestMethod='get')
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod($requestMethod)
            ->setUrl($url)
            ->setData($params)
            ->send();
        if ($response->isOk) {
            return $response->content;
        } else {
            return null;
        }
    }

}