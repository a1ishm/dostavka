<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Авторизация</title>
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
    <h3>Авторизация</h3>
    <form class="row g-3 needs-validation" novalidate>
        <div class="col-md-6">
            <label for="name" class="form-label">Имя</label>
            <input type="text" class="form-control" id="name" required>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Эл. почта</label>
            <input type="email" class="form-control" id="email" required>
        </div>
        <div class="col-md-6">
            <label for="geo" class="form-label">Адрес доставки</label>
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

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const geoInput = document.getElementById('geo');
    const loaderEl = document.getElementById('geo-loader-div');
    const locationEl = document.getElementById('location');

    const data = {name: "", email: "", geo: "", flag: ""};

    tg.onEvent('mainButtonClicked', () => {
        tg.sendData(JSON.stringify(data));
    });

    nameInput.addEventListener("input", () => {
        let val = nameInput.value.trim();
        if (val === '') {
            data.name = '';
            toggleClass(nameInput, 'is-invalid', 'is-valid');
        } else {
            data.name = val;
            toggleClass(nameInput, 'is-valid', 'is-invalid');
        }
        checkForm();
    });

    emailInput.addEventListener("input", () => {
        let val = emailInput.value.trim();
        const re = /\w+@\w+\.\w{2,6}/;
        if (re.test(val)) {
            data.email = val;
            toggleClass(emailInput, 'is-valid', 'is-invalid');
        } else {
            data.email = '';
            toggleClass(emailInput, 'is-invalid', 'is-valid');
        }
        checkForm();
    });

    let timeoutId;
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
        }, 6000);
    });

    function checkForm() {
        if (!data.name || !data.email || !data.geo) {
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
