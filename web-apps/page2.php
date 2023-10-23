<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$store_id = $_GET['id'];
$store_name = $_GET['name'];

$per_page = 6;
if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];
    if ($page < 1) {
        $page = 1;
    }
    $start = get_start($page, $per_page);
    $products = get_products($start, $per_page, $store_id);
    ob_start();
    foreach ($products as $product) {
        require __DIR__ . '/product_tpl.php';
    }
    $html = ob_get_clean();
    echo $html;
    die;
} else {
    $page = 1;
    $start = get_start($page, $per_page);
    $products = get_products($start, $per_page, $store_id);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заведение #</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div hidden id="store_id"><?php echo $store_id; ?></div>
<div hidden id="store_name"><?php echo $store_name; ?></div>
<div class="container my-3" id="<?php echo 'hide-me' ?>">
    <div class="row">
        <div class="col-12">

            <nav class="fixed-top">
                <div class="nav nav-tabs animate__animated animate__fadeInDown" id="nav-tab" role="tablist">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#nav-store" type="button"
                            role="tab">Меню
                    </button>
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#nav-cart" type="button" role="tab">
                        Корзина <span class="badge rounded-pill bg-danger cart-sum">0</span></button>
                </div>
            </nav>

            <div class="tab-content mt-3" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-store" role="tabpanel">
                    <h2 class="animate__animated animate__fadeInDown text-center"><?php echo $store_name; ?></h2>
                    <div class="row animate__animated animate__fadeIn" id="products-list">
                        <?php foreach ($products as $product): ?>
                            <?php require __DIR__ . '/product_tpl.php'; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center animate__animated animate__fadeInUp" id="loader">
                        <button class="btn btn-secondary" id="loader-btn">ещё...</button>
                        <img src="img/loader.svg" alt="loader.svg" id="loader-img" class="loader-img">
                    </div>

                </div>

                <div class="tab-pane fade show" id="nav-cart" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <h2 class="animate__animated animate__fadeInDown text-center">Корзина</h2>

                            <p class="empty-cart">Корзина пуста</p>

                            <table class="table animate__animated animate__fadeInUp">
                                <thead>
                                <tr class="text-center">
                                    <th scope="col" style="font-size: 14px">#</th>
                                    <th scope="col" style="font-size: 14px">Фото</th>
                                    <th scope="col" style="font-size: 14px">Позиция</th>
                                    <th scope="col" style="font-size: 14px">Кол-во</th>
                                    <th scope="col" style="font-size: 14px">Цена</th>
                                    <th scope="col">🗑</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js?v=1.12"></script>

</body>
</html>
