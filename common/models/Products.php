<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "products".
 *
 * @property integer $id
 * @property integer $category_id
 * @property integer $article
 * @property string $title
 * @property string $country
 * @property integer $mass
 * @property string $unit
 * @property integer $price
 * @property string $image
 * @property integer $status
 */
class Products extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'products';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category_id', 'article', 'mass', 'price', 'status'], 'integer'],
            [['unit'], 'string', 'max' => 32],
            [['title', 'image'], 'string', 'max' => 255],
            [['country'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'ИД категории',
            'article' => 'Артикул',
            'title' => 'Название',
            'country' => 'Страна производитель',
            'mass' => 'Вес (г)',
            'unit' => 'Ед. измерения',
            'price' => 'Цена',
            'image' => 'Изображение',
            'status' => 'Статус',
        ];
    }
}
