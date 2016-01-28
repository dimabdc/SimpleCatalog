<?php

/* @var $this yii\web\View */
/* @var $categoryDataProvider yii\data\ActiveDataProvider */
/* @var $productDataProvider yii\data\ActiveDataProvider */
/* @var $breadcrumbs array */

use yii\widgets\ListView;

$this->title = 'My Yii Application';
?>
<div class="site-index">
    <?php
    $this->params['breadcrumbs'] = $breadcrumbs;
    echo ListView::widget([
        'dataProvider' => $categoryDataProvider,
        'itemView' => '_category',
        'itemOptions' => [
            'tag' => 'li',
            'style' => "display:inline-table;",
            'class' => "btn btn-default btn-xs"
        ],
        'options' => [
            'tag' => 'ul',
        ],
        'emptyText' => '',
        'layout' => "{items}",
    ]);

    echo ListView::widget([
        'dataProvider' => $productDataProvider,
        'itemView' => '_item',
        'itemOptions' => [
            'tag' => 'div',
            'class' => 'products_card'
        ],
        'options' => [
            'tag' => 'div',
            'class' => 'products'
        ],
        'emptyText' => 'Продуктов нет в данной категории',
        'layout' => "{items}{pager}",
    ]);
    ?>

</div>
