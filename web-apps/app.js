const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

function setStyles() {
    const computedBackgroundColor = window.getComputedStyle(document.body).getPropertyValue('background-color');
    let btn_class = '';
    if (isLightColor(computedBackgroundColor)) {
        btn_class = 'btn-dark';
    } else {
        btn_class = 'btn-light';
    }
    const buttons = document.querySelectorAll('button[id="btn-add"]');
    buttons.forEach(button => {
        button.classList.add(btn_class);
    });
}

setStyles();
const targetElement = document.getElementById('products-list');
const observer = new MutationObserver((mutationsList, observer) => {
    mutationsList.forEach(mutation => {
        if (mutation.type === 'childList') {
            setStyles();
        }
    });
});
const config = { childList: true, subtree: true }; // Настройки наблюдения
observer.observe(targetElement, config);

function isLightColor(color) {
    const rgba = color.match(/\d+/g);
    if (!rgba) return false;
    const brightness = (parseInt(rgba[0]) * 299 + parseInt(rgba[1]) * 587 + parseInt(rgba[2]) * 114) / 1000;
    return brightness > 128;
}

const productsContainer = document.getElementById('products-list');
const loaderBtn = document.getElementById('loader-btn');
const loaderImg = document.getElementById('loader-img');
const cartTable = document.querySelector('table');
let page = 1;

const STORE_ID = document.getElementById('store_id').textContent;
const STORE_NAME = document.getElementById('store_name').textContent;

async function getProducts() {
    const res = await fetch(`page2.php?id=${STORE_ID}&name=${STORE_NAME}&page=${page}`);
    return res.text();
}

async function showProducts() {
    const products = await getProducts();
    if (products) {
        productsContainer.insertAdjacentHTML('beforeend', products);
    } else {
        loaderBtn.classList.add('d-none');
    }
}



loaderBtn.addEventListener('click', () => {
    loaderImg.classList.add('d-inline-block');
    setTimeout(() => {
        page++;
        showProducts()
            .then(() => {
                productQty(cart);
            });
        loaderImg.classList.remove('d-inline-block');
        setStyles();
    }, 500);
});

function add2Cart(product) {
    let id = product.id;
    if (id in cart) {
        cart[id]['qty'] += 1;
    } else {
        cart[id] = product;
        cart[id]['qty'] = 1;
    }
    getCartSum(cart);
    productQty(cart);
    cartContent(cart);
}

function getCartSum(items) {
    let cartSum = Object.entries(items).reduce(function (total, values) {
        const [, value] = values;
        return total + (value['qty'] * value['price']);
    }, 0);
    document.querySelector('.cart-sum').innerText = cartSum / 100 + '₽';
    return cartSum;
}

function productQty(items) {
    document.querySelectorAll('.product-cart-qty').forEach(item => {
        let id = item.dataset.id;
        if (id in items) {
            item.innerText = items[id]['qty'];
        } else {
            item.innerText = '';
        }
    })
}

function cartContent(items) {
    let cartTableBody = document.querySelector('.table tbody');
    let cartEmpty = document.querySelector('.empty-cart');
    let qty = Object.keys(items).length;
    if (qty) {
        tg.MainButton.show();
        tg.MainButton.setParams({
            text: `ОФОРМИТЬ ДОСТАВКУ: ${getCartSum(items) / 100}₽`,
            color: '#561212'
        });
        cartTable.classList.remove('d-none');
        cartEmpty.classList.remove('d-block');
        cartEmpty.classList.add('d-none');
        cartTableBody.innerHTML = '';
        Object.keys(items).forEach(key => {
            cartTableBody.innerHTML += `
<tr class="align-middle animate__animated">
    <th scope="row">${key}</th>
    <td><img src="img/${items[key]['img']}" class="cart-img" alt=""></td>
    <td>${items[key]['title']}</td>
    <td>${items[key]['qty']}</td>
    <td>${items[key]['price'] / 100}</td>
    <td data-id="${key}"><button class="btn del-item">🗑</button></td>
</tr>
`;
        });
    } else {
        tg.MainButton.hide();
        cartTableBody.innerHTML = '';
        cartTable.classList.add('d-none');
        cartEmpty.classList.remove('d-none');
        cartEmpty.classList.add('d-block');
    }
}

let cart = {};
getCartSum(cart);
productQty(cart);
cartContent(cart);

productsContainer.addEventListener('click', (e) => {
    if (e.target.classList.contains('add2cart')) {
        e.preventDefault();
        e.target.classList.add('animate__rubberBand');
        add2Cart(JSON.parse(e.target.dataset.product));
        setTimeout(() => {
            e.target.classList.remove('animate__rubberBand');
        }, 1000);
    }
});

cartTable.addEventListener('click', (e) => {
    const target = e.target.closest('.del-item');
    if (target) {
        let id = target.parentElement.dataset.id;
        target.parentElement.parentElement.classList.add('animate__zoomOut');
        setTimeout(() => {
            delete cart[id];
            getCartSum(cart);
            productQty(cart);
            cartContent(cart);
        }, 300);
    }

});

tg.MainButton.onClick(() => {
    if (getCartSum(cart) < 10000) {
        alert('Минимальная сумма заказа: 100₽')
    } else {
        fetch('./../index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json;charset=utf-8',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                store_id: STORE_ID,
                query_id: tg.initDataUnsafe.query_id,
                user: tg.initDataUnsafe.user,
                cart: cart,
                total_sum: getCartSum(cart)
            })
        }).then(response => response.json()).then(data => {
            console.log(data);
            if (data['res']) {
                let cart = {};
                getCartSum(cart);
                productQty(cart);
                cartContent(cart);
                tg.close();
            } else {
                alert(data['answer']);
            }
        });
    }
});