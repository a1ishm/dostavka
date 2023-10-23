<?php

error_reporting(-1);
ini_set('display_errors', 0);
ini_set('log_errors', 'on');
ini_set('error_log', __DIR__ . '/errors.log');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
$phrases = require_once __DIR__ . '/phrases.php';
require_once __DIR__ . '/keyboards.php';
require_once __DIR__ . '/functions.php';

/**
 * @var array $phrases
 * @var array $inline_keyboard11
 * @var array $keyboard1
 * @var array $keyboard2
 */

$telegram = new \Telegram\Bot\Api(TOKEN);
$update = $telegram->getWebhookUpdate();
debug($update);

$text = $update['message']['text'] ?? '';
$name = $update['message']['from']['first_name'] ?? 'Guest';
$store_id = $update['store_id'] ?? -1;

if (isset($update['message']['chat']['id'])) {
    $chat_id = $update['message']['chat']['id'];
} elseif (isset($update['user']['id'])) {
    $chat_id = (int)$update['user']['id'];
    $query_id = $update['query_id'] ?? '';
    $cart = $update['cart'] ?? [];
    $total_sum = $update['total_sum'] ?? 0;
    $total_sum = (int)$total_sum;
    $geo = $update['geo'] ?? '';
    $flag = $update['flag'] ?? '';
} elseif (isset($update['pre_checkout_query']['id'])) {
    $chat_id = $update['pre_checkout_query']['id'];
}

if (!$chat_id) {
    die;
}

if ($text == '/start') {
    if (!check_chat_id($chat_id)) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => sprintf($phrases['start'], $name),
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard1),
        ]);
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['show_stores'],
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard2),
        ]);

        $stores = get_stores();
        $keyboards = get_keyboards($stores);
        $i = 0;
        foreach ($keyboards as $keyboard) {
            $telegram->sendPhoto([
                'chat_id' => $chat_id,
                'photo' => fopen(__DIR__ . '/web-apps/img/' . $stores[$i]['img'], 'r'),
                'caption' => '"' . $stores[$i]['name'] . '"' . PHP_EOL . $stores[$i]['description'],
                'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard),
            ]);
            ++$i;
        }
    }
} elseif ($text == $phrases['btn_unsubscribe']) {
    if (delete_subscriber($chat_id)) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['success_unsubscribe'],
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard1),
        ]);
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['error_unsubscribe'],
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard2),
        ]);
    }
} elseif ($text == '/geo') {
    if (check_chat_id($chat_id)) {
        $geo = get_location($chat_id);
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['change_loc_text'] . $geo[0]['geo'],
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($inline_keyboard11),
        ]);
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['user_notfound'],
            'parse_mode' => 'HTML',
        ]);
    }
} elseif (isset($update['message']['web_app_data'])) {
    $btn = $update['message']['web_app_data']['button_text'];
    $data = json_decode($update['message']['web_app_data']['data'], 1);

    if (!check_chat_id($chat_id)) {
        if (!empty($data['name']) && !empty($data['email']) && !empty($data['geo'])) {
            if (add_subscriber($chat_id, $data)) {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $phrases['success_subscribe'] . $data['geo'],
                    'parse_mode' => 'HTML',
                    'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard2),
                ]);

                $stores = get_stores();
                $keyboards = get_keyboards($stores);
                $i = 0;
                foreach ($keyboards as $keyboard) {
                    $telegram->sendPhoto([
                        'chat_id' => $chat_id,
                        'photo' => fopen(__DIR__ . '/web-apps/img/' . $stores[$i]['img'], 'r'),
                        'caption' => $stores[$i]['description'],
                        'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard),
                    ]);
                    ++$i;
                }
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $phrases['error_subscribe'],
                    'parse_mode' => 'HTML',
                    'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard1),
                ]);
            }
        }
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['duplicate_user'],
            'parse_mode' => 'HTML',
            'reply_markup' => new \Telegram\Bot\Keyboard\Keyboard($keyboard1),
        ]);
    }
} elseif (!empty($query_id) && !empty($cart) && !empty($total_sum)) {
    if (!check_chat_id($chat_id)) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['order_unsub'],
            'parse_mode' => 'HTML',
        ]);

        $res = ['res' => false, 'answer' => 'Error'];
        echo json_encode($res);
        die;
    }

    if (check_cart($cart, $total_sum) && $total_sum >= MINIMAL_BILL) {
        if (!$order_id = add_order($chat_id, $update, $store_id)) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $phrases['order_err'],
                'parse_mode' => 'HTML',
            ]);
            $res = ['res' => false, 'answer' => 'Cart Error'];
            echo json_encode($res);
            die;
        }

        $order_products = [];
        foreach ($cart as $item) {
            $order_products[] = [
                'label' => "{$item['title']} x {$item['qty']}",
                'amount' => $item['price'] * $item['qty'],
            ];
        }

        try {
            $telegram->sendInvoice([
                'chat_id' => $chat_id,
                'title' => $phrases['invoice_title'] . $order_id,
                'description' => $phrases['invoice_description'],
                'payload' => $order_id,
                'provider_token' => STRIPE_TOKEN,
                'currency' => 'RUB',
                'prices' => $order_products,
                'photo_url' => PAYMENT_IMG,
                'photo_width' => 640,
                'photo_height' => 427,

            ]);
            $res = ['res' => true];
            echo json_encode($res);
            die;
        } catch (\Telegram\Bot\Exceptions\TelegramSDKException $e) {
            $res = ['res' => false, 'answer' => $e->getMessage()];
            echo json_encode($res);
            die;
        }
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['cart_error'],
            'parse_mode' => 'HTML',
        ]);
        $res = ['res' => false, 'answer' => 'Cart Error'];
    }

    echo json_encode($res);
    die;
} elseif (!empty($geo) && $flag == 'chnglctn') {
    if (change_location($chat_id, $geo)) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['success_change_loc'] . $geo,
            'parse_mode' => 'HTML',
        ]);
        $res = ['res' => true];
    } else {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $phrases['error_change_loc'],
            'parse_mode' => 'HTML',
        ]);
        $res = ['res' => false, 'answer' => 'Address changing Error'];
    }

    echo json_encode($res);
    die;
} elseif (isset($update['pre_checkout_query'])) {
    $telegram->answerPreCheckoutQuery([
        'pre_checkout_query_id' => $chat_id,
        'ok' => true,
    ]);
} elseif (isset($update['message']['successful_payment'])) {
    $payment_id = $update['message']['successful_payment']['provider_payment_charge_id'];
    $order_id = $update['message']['successful_payment']['invoice_payload'];
    $sum = $update['message']['successful_payment']['total_amount'] / 100;
    $curr = $update['message']['successful_payment']['currency'];
    toggle_order_status($order_id, $payment_id);
    $telegram->sendMessage([
        'chat_id' => $chat_id,
        'text' => $phrases['payment_part1'] . $order_id . ' ' . $phrases['payment_part2'] . ' ' . $sum . ' ' . $curr . '.' . PHP_EOL . $phrases['payment_part3'],
        'parse_mode' => 'HTML',
    ]);
} else {
    $telegram->sendMessage([
        'chat_id' => $chat_id,
        'text' => $phrases['error'],
        'parse_mode' => 'HTML',
    ]);
}
