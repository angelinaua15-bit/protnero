<?php
session_start();
$order = isset($_SESSION['order']) ? $_SESSION['order'] : '-';
$name = isset($_SESSION['name']) ? $_SESSION['name'] : '-';
$phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '-';

$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'ru';
$translationDir = dirname(__DIR__) . '/translations';
if (file_exists($translationDir)) {
    $i18nFile = file_exists("$translationDir/$language.php") ? "$translationDir/$language.php" : "$translationDir/ru.php";
    include_once $i18nFile;
}

$fbp = isset($_SESSION['fbp']) ? $_SESSION['fbp'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $i18n['newsuccess_thanks'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=PT+Sans:400,400i,700&amp;subset=cyrillic" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="57x57" href="img/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="img/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="img/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="img/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="img/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="img/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="img/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="img/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="img/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
    <link rel="manifest" href="img/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="img/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link href="css/bootstrap.css" rel="stylesheet">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/media.css" media="all and (max-width:1200px)">
</head>
<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-12 thanks">
                    <h2><?=$i18n['newsuccess_thanks'];?></h2>
                    <p><?=$i18n['newsuccess_orderaccept'];?> <?=$i18n['newsuccess_contactyou'];?></p>
                </div>
                <div class="col-lg-offset-5 col-md-offset-5 col-sm-offset-5 col-lg-2 col-md-2 col-sm-2 col-xs-12">
                    <div class="info">
                        <p><?=$i18n['newsuccess_orderinfo'];?></p>
                        <p><span class="blue big"><?= $order ?></span></p>
                        <p><?= $phone ?></p>
                        <p><?= $name ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="main">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
                    <h2><?=$i18n['newsuccess_howto'];?></h2>
                    <p><?=$i18n['newsuccess_confirm'];?></p>
                    <div class="discount">
                        <div class="manager">
                            <img src="img/manager_1.png" alt="">
                            <div class="right">
                                <p><?=$i18n['newsuccess_discount'];?></p>
                            </div>
                            <div class="text_manager">
                                <p><span><?=$i18n['managername'];?></span><br>
                                <?=$i18n['manager'];?></p>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                    <div class="form_wrapper">
                        <div class="order_form">
                            <p><?=$i18n['newsuccess_email'];?></p>

                            <form action="#" method="post" class="main-form">
                                <!--<p></p>-->
                                <input class="name" name="name" type="hidden"  value="<?= $name ?>">
                                <p><?=$i18n['newsuccess_getemail'];?></p>
                                <input class="email" name="email" type="text" placeholder="e-mail" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="xxxxxx@yyyyy.zzz">
                                <button type="submit" class="main-form__button"><?=$i18n['newsuccess_instr'];?></button>
                                <div class="footer_form">
                                    <div><img src="img/check.png" alt=""></div>
                                    <p><?=$i18n['newsuccess_info'];?> <br>
                                        <?=$i18n['newsuccess_agree'];?> </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="js/jquery-2.2.4.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("form.main-form").on("submit", function () {
            $.post(
                "https://system.trackerlead.biz/user/subscribe",
                {feedback_email: $("input.main-form__email").val(), orderid: "<?= $order ?>"}
            );
            $(this).fadeOut("fast", function () {
                $(this).parent().append("<p style='font-size: 1.2em; line-height: 2em; text-align: center;'>Спасибо!</p>");
            });
            return false;
        });
    });
</script>
<script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= $fbp ?>');
    fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?=$fbp?>&ev=PageView&noscript=1"/></noscript>
</body>
</html>