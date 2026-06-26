## Результат тестового задания для  1С-Битрикс

### Окружение:
 - 1С-Битрикс: Управление сайтом 26.150.0
 - apache web-server, php 8.2, mysql8.4


### Компоненты:
- **news.list** - основной компонент, доступен в визуальном редакторе
- news.detail - вспомогательный компонент, выводит детальную страницу
- news.filter - вспомогательный компонент, выводит фильтры

### Инструкция:
 1. Разместить все компоненты в папке `local/components/<vendor_name>`,  где `<vendor_name>` - любое название папки
 1. Создать в корне сайта папку `news_lubaro` (название любое, необходимо для пунктов 3 и 4 далее) и внутри файл `index.php` с содержимым:

    ```php
    <?php
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
    $APPLICATION->SetTitle("*Новости*");
    ?><br>
    <? $APPLICATION->IncludeComponent(
        "bitrix:breadcrumb",
        "",
        array()
    ); ?><br>
    <? $APPLICATION->IncludeComponent(
        "lubaro:news.list",
        "",
        array(
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "DETAIL_URL" => "",
            "IBLOCK_ID" => "1",
            "IBLOCK_TYPE" => "news",
            "NEWS_COUNT" => "10",
            "SEF_FOLDER" => "/news_lubaro/",
            "SEF_MODE" => "Y",
            "SEF_URL_TEMPLATES" => array("detail" => "#ELEMENT_ID#/"),
            "SET_BROWSER_TITLE" => "Y",
            "SET_TITLE" => "Y"
        )
    ); ?><br>
    <br><?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
    ```

 1. Добавить в массив в файле `urlrewrite.php` элемент:
    ```php
    [
        'CONDITION' => '#^/news_lubaro/#',
        'RULE' => '',
        'ID' => 'lubaro:news.list',
        'PATH' => '/news_lubaro/index.php',
        'SORT' => 100
    ]
    ```
    В `CONDITION`  прописать желаемый адрес.

### Результат:
При открытии URL: http://**<your_bitrix_site_address>**/news_lubaro/ будет открываться список новостей.

Также редактирование страницы и компонента доступно в админке в визуальном редакторе.