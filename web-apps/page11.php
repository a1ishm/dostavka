<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Смена адреса</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script src="telegram-web-app.js"></script>
    <style>
        body {
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
        }

        button {
            background-color: var(--tg-theme-button-color);
            color: var(--tg-theme-button-text-color);
            border: 0;
            padding: 5px 15px;
        }

        #location {
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h3>Смена адреса доставки</h3>
    <form class="row g-3 needs-validation" novalidate>
        <div class="col-md-6">
            <label for="geo" class="form-label">Новый адрес доставки</label>
            <input type="text" class="form-control" id="geo" required>
        </div>
        <div id="geo-loader-div" style="display: none">
            Проверяем корректность адреса...
            <img src="img/loader.svg" alt="loader.svg" id="loader-img" class="loader-img">
        </div>
        <div id="location"></div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const tg = window.Telegram.WebApp;
    tg.ready();
    tg.expand();

    const geoInput = document.getElementById('geo');
    const loaderEl = document.getElementById('geo-loader-div');
    const locationEl = document.getElementById('location');

    const data = {geo: "", flag: "chnglctn"};

    tg.MainButton.onClick(() => {
        fetch('./../index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json;charset=utf-8',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                query_id: tg.initDataUnsafe.query_id,
                user: tg.initDataUnsafe.user,
                geo: data.geo,
                flag: data.flag
            }),
        }).then(response => response.json()).then(data => {
            console.log(data);
            if (data['res']) {
                tg.close();
            } else {
                alert(data['answer']);
            }
        });
    });

    let timeoutId; // Переменная для хранения идентификатора таймера

    geoInput.addEventListener("input", () => {
        loaderEl.style.display = 'block';
        locationEl.textContent = '';

        data.geo = '';
        toggleClass(geoInput, 'is-invalid', 'is-valid');

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(async () => {
            let val = geoInput.value.trim();
            const geo = await getAddress(val);

            if (geo !== '') {
                data.geo = geo;
                toggleClass(geoInput, 'is-valid', 'is-invalid');
                loaderEl.style.display = 'none';
                locationEl.textContent = geo + '✅';
            } else {
                data.geo = '';
                toggleClass(geoInput, 'is-invalid', 'is-valid');
                loaderEl.style.display = 'none';
                locationEl.textContent = 'Некорректный адрес❌';
            }
            checkForm();
        }, 8500);
    });

    function checkForm() {
        if (!data.geo) {
            tg.MainButton.hide();
        } else {
            tg.MainButton.setParams({
                text: "Send Form",
                color: '#d260aa',
                text_color: '#fff'
            });
            tg.MainButton.show();
        }
    }

    function toggleClass(field, class_add, class_remove) {
        field.classList.add(class_add);
        field.classList.remove(class_remove);
    }

    async function getAddress(address) {
        const url = `https://nominatim.openstreetmap.org/search?q=${address}&format=json`;

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.length > 0 && response[0].hasOwnProperty('display_name')) {
                        resolve(response[0]['display_name']);
                    } else {
                        resolve('');
                    }
                } else {
                    reject('Ошибка: ' + xhr.status);
                }
            };
            xhr.send();
        });
    }

</script>
</body>
</html>

