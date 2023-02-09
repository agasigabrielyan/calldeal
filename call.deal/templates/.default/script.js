let ajaxInProcces = false;
const ajaxUrl = "/local/components/devconsult/call.deal/ajax.php";
const dadataAjaxUrl = "/local/components/devconsult/call.deal/dadata.php";

// создание дополнительной кнопки в форме звонка
BX.addCustomEvent("onAjaxSuccessFinish", BX.delegate(function(e) {
    createAdditionalButton();
}));

// добавление новой строки товара в форме
$(document).on("click", ".add-new-products-row", function(e) {
    let currentFormId = this.dataset.formid;
    addNewGoodsRow( currentFormId );
});

// удаление строки товара в форме, если строка единственная оставшаяся, должен удаляться так же и thead
$(document).on("click", "button[data-delete]", function( event ) {
    let currentForm = $(".documents__wrapper_" + this.dataset.identifier);
    let goodIdentifier = this.parentNode.parentNode.querySelector("[data-goodid]").value;
    let productid = this.dataset.productid;

    $(".document-created__" + goodIdentifier).remove();

    let identifier = this.dataset.identifier;

    removeDocumentsOfCurrentProduct( productid, identifier ); // удаляем документы, связанные с товаром
    
    if( event.target.dataset.metrostation ) {
        handleMetroStation( event.target.dataset.metrostation, identifier);        // если товар metrostation, то удаляем
    }
    removeGoodsRow( event, this, identifier );
});

// при помещении курсора в поле товара подгрузим список товаров для выбора пользователем
$(document).on("focus","[data-good]",function() {
    openGoodsWrapper( this );
});

// при уходе фокуса с поля добавления товара удалим товары, однако если при этом не выбран товар из этого списка
$(document).on("focusout","[data-good]",function( e ) {
    // если у этого элемента есть goods-wrapper, т.е список товаров, то его не убираем
    let goodsWrappersExists = event.target.parentNode.children[1].classList.contains("goods-wrapper");
    if( !( goodsWrappersExists ) ) {
        removeGoodsWrapper();
    }
});

// так же удаляем goods-wrapper товары если мы нажали в любом месте формы и это не input ввода имени товара
$(document).on("click", ".deal-form", function( e ) {
    if( !(e.target.classList.contains("ui-ctl-element")) ) {
        removeGoodsWrapper();
    }
})

// поиск товаров в списке товаров при выборе
$(document).on("keyup","[data-good]",function() {
    let currentInput = this;
    addLoader(this);
    setTimeout( function() {
            findGood( currentInput );
        } , 100 );
    setTimeout( function() {
        removeLoader();
    } , 100 );
});

// нажатие на конкретный элемент из списка, пересчет корзины товаров
$(document).on("click", ".catalog-item", function(e) {
    let ajaxResult;
    let id = this.dataset.id;
    // получим наименование товара и его цену
    ajaxResult = addGoodIntoBasket( id, ajaxResult );

    // запишем полученные данные в соответствующие td input
    let currentTr = this.parentNode.parentNode.parentNode;
    // добавим идентификатор товара к строке в виде data-productid атрибута
    $(currentTr).attr('data-productid', id);
    let currentTrGood = currentTr.querySelector("[data-good]");
        let identifier = currentTrGood.dataset.identifier;
    let currentTrPrice = currentTr.querySelector("[data-price]");
    let currentTrId = currentTr.querySelector("[data-goodid]");

    currentTrGood.value = ajaxResult['NAME'];
    currentTrPrice.value = ajaxResult['PRICE'];
    currentTrId.value = id;

    // пересчитаем строку
    reCalculateTrRow( currentTr, identifier );
});

// обработка поля input при изменении документа, перерасчет корзины
$(document).on("keyup", ".ui-ctl-element_custom", function( e ) {
    let currentTr = this.parentNode.parentNode;
    let identifier = this.dataset.identifier;
    reCalculateTrRow( currentTr, identifier );
});

// обработка кнопки СОХРАНИТЬ создания сделки
$(document).on("click",".ui-btn-success_custom", (e) => {
    e.preventDefault();
    event.target.classList.add("ui-btn-clock");
    let identifier = event.target.dataset.identifier;
    setTimeout(function() { createDeal( identifier ) }, 1000);
});

// открытие документа для редактирования формы
$(document).on("click",".document-created__open",function(e) {
    // укажем, что этот документ уже редактировался - отобразим иконку checked.svg
    this.parentNode.parentNode.querySelector(".document-created__edited").classList.remove("document-created__edited_hidden");
    if( this.dataset.link ) {
        BX.SidePanel.Instance.open( this.dataset.link );
    }
});

// обработка кнопки нажатия на стадию сделки radio
$(document).on("click", ".deal-form__button", function(e) {
    // обнулим все radiobuttons
    $(".deal-form__button").addClass("deal-form__button_opacitied");
    $(".STAGE_RADIO").prop('checked', false);

    // выделим input type radio в этой ссылке
    this.querySelector("input[type='radio']").checked = true;
    this.classList.remove("deal-form__button_opacitied");
});

// обработчик поля выбора метро, при его изменении необходимо удалить из товаров товар метро доставка data-metrostation
$(document).on("change","select[name='UF_CRM_METRO_STATION']",function(e) {
    
    let currentForm = document.querySelector(".deal-form__itself_" + this.dataset.identifier);
    let trProductWithMetroStation = currentForm.querySelector("[data-metrostation]").parentNode.parentNode;
    
    trProductWithMetroStation.parentNode.removeChild( trProductWithMetroStation );
});






// 1. создает кнопку в интерфейсе звонка на основе идентификаторов заявок, которые в форме отображаются
function createAdditionalButton() {
    let allLinksWrappers = document.querySelectorAll('.crm-card-show-detail-info-main-inner');
    let allLinkStatesesNames = $(".crm-card-show-detail-info-main-status .crm-list-stage-bar-title");
    let allLinks = [];
    let allLinkNames = [];

    if( typeof allLinksWrappers != "undefined" ) {
        for( let j=0; j<allLinksWrappers.length; j++ ) {
            allLinks.push( allLinksWrappers[j].getElementsByTagName("a")[0].attributes.href.nodeValue );
        }
    }

    if( typeof allLinkStatesesNames != "undefined" ) {
        for( let m=0; m<allLinkStatesesNames.length; m++ ) {
            allLinkNames.push( allLinkStatesesNames[m].textContent );
        }
    }

    let allIdentifiers = [];
    // 1) получим из формы обратного звонка сформированные сделки их может быть несколько
    if( allIdentifiers.length <= 0 ) {
        if( allLinks.length > 0 ) {
            for( let m=0; m<allLinks.length; m++ ) {
                let foundIdentifier = (allLinks[m].match(/(\d+)/))[0];
                allIdentifiers.push( foundIdentifier );
            }
        }
    }

    // 2) На каждую полученную ссылку нам нужно создать свою кнопку, если в allIdentifiers есть какие-то идентификаторы, то мы создаем для каждого идентификатора свою кнопку
    let buttonContainer = document.querySelector(".im-phone-sidebar-tabs-left");    // нода в форме звонка куда мы будем добавлять данные
    if( allIdentifiers.length>0 ) {
        for( let w=0; w<allIdentifiers.length; w++ ) {
            let isLinkAlreadExists = false;

            if( BX('call-deal-form' + allIdentifiers[w]) ) {
                isLinkAlreadExists = true;
            }

            let textValueForDealLink = "Заявка " + allIdentifiers[w] + "<br/>" + allLinkNames[w];

            if( buttonContainer !== null && !isLinkAlreadExists ) {
                let dealFormButton = BX.create(
                    "span",
                    {
                        props: {
                            className: 'im-phone-sidebar-tab',
                            id: 'call-deal-form' + allIdentifiers[w]
                        },
                        html: textValueForDealLink,
                        events: {
                            click: function() {
                                getDealForm( allIdentifiers[w] );
                            }
                        }
                    }
                );
                BX.append(dealFormButton, buttonContainer);
            }

        }
    }
}

// 2. получает компонент с формой с помощью ajax запроса для отображения в popup
function getDealForm( formid ) {
    BX.showWait();
    let post = {};
    post['identifier'] = formid;
    BX.ajax.post(
        "/customDealForm.php",
        post,
        function ( data ) {
            openPopupWithForm( data, formid );
            BX.closeWait();
        }
    );
}

// 3. открывает popup с формой
function openPopupWithForm( data, formid ) {
   let popupWindowIdentifier = 'initial-form-popup' + formid + "__" + (Math.random() + 1).toString(36).substring(7);
   let initialFormPopup = BX.PopupWindowManager.create( popupWindowIdentifier , null, {
     width: 900, // ширина окна
     height: 600, // высота окна
     zIndex: 100, // z-index
	 autoHide: false,
	 offsetLeft: 0,
	 offsetTop: 0,
	 overlay : true,
	 draggable: {restrict:true},
	 closeByEsc: true,
	 closeIcon: { right : "0", top : "0"},
	 content: data,
	 events: {
       onPopupShow: function() {
           calculateFullSumm( formid );
       },
       onPopupClose: function() {
           BX.PopupWindowManager.getCurrentPopup().destroy();
       }
	 }
	});

	initialFormPopup.show();
}

// 4. создание нового товара в интерфейсе
function addNewGoodsRow( formid ) {
    // проверим если нет шапки для таблицы, то создадим ее и добавим к таблице
    let dealFormClass = ".deal-form-" + formid;
    let dealForm = document.querySelector( dealFormClass );
    let dealFormGroupProductsCurrentForm = "deal-form__group_products_" + formid;

    if( !( dealForm.querySelector("thead") ) ) {
        let tHead = BX.create(
                        "thead",
                        {
                            "children" : [
                                BX.create(
                                    "tr",
                                    {
                                        "children": [
                                            BX.create("th",{ text: "Товар" }),
                                            BX.create("th",{ text: "Цена (руб)" }),
                                            BX.create("th",{ text: "Количество (шт)" }),
                                            BX.create("th",{ text: "Сумма скидки" }),
                                            BX.create("th",{ text: "Сумма (руб)" }),
                                            BX.create("th",{ text: "" }),
                                            BX.create("th",{ text: "" }),
                                        ]
                                    }
                                )
                            ]
                        }
                    );
        $("." + dealFormGroupProductsCurrentForm + " table").append( tHead );
    }
    
    // добавим новую строчку товара
    let newRow =
        BX.create(
            "tr",
            {
                "children": [
                    BX.create(
                        'td',
                        {
                            html: '<input data-identifier="' + formid + '" autocomplete="off" name="goods[]" value="" data-good type="text" class="ui-ctl-element ui-ctl-element_custom" placeholder="Товар">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<input data-identifier="' + formid + '" autocomplete="off" name="prices[]" value="" data-price type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Цена (руб)">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<input data-identifier="' + formid + '" autocomplete="off" name="quantity[]" value="1" data-count type="number" class="ui-ctl-element ui-ctl-element_custom" placeholder="Количество (шт)">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<input data-identifier="' + formid + '" autocomplete="off" name="sale[]" value="0" data-salesumm type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Скидка">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<input data-identifier="' + formid + '" autocomplete="off" value="" data-summ type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Сумма (руб)">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<input autocomplete="off" name="ids[]" value="" data-goodid type="hidden">'
                        }
                    ),
                    BX.create(
                        'td',
                        {
                            html: '<button data-productid="' +  + '" data-identifier="' + formid + '" data-delete title="Удалить этот товар">x</span>'
                        }
                    )
                ]
            }
        );
    // Добавляем новые товары к текущей форме

    $("." + dealFormGroupProductsCurrentForm + " tbody").append( newRow );
}

// 4. удаление строки товара, если строка единственная в таблице, удаляем так же thead
function removeGoodsRow( event, removedRowObj, identifier ) {
    event.preventDefault();
    let tBody = removedRowObj.parentNode.parentNode.parentNode;
    let rows = tBody.children;
    if( rows.length < 2 ) {
        let tHead = tBody.nextElementSibling;
        tHead.parentNode.removeChild(tHead);
    }
    tBody.removeChild( removedRowObj.parentNode.parentNode );
    calculateFullSumm( identifier );
}

// 5. метод, который открывает список товаров
function openGoodsWrapper( obj ) {
    if( document.querySelector(".goods-wrapper") ) {
        let goodsWrapper = document.querySelector(".goods-wrapper");
        goodsWrapper.parentNode.removeChild( goodsWrapper );
    }
    if( !(document.querySelector(".goods-wrapper") ) ) {
        addLoader( obj );
        let ajaxResult;
        let goods = returnGoodsViaAjax( ajaxResult );
        let goodsWrapper = document.createElement("div");
        goodsWrapper.innerHTML = goods;
        goodsWrapper.classList.add("goods-wrapper");
        obj.parentNode.appendChild( goodsWrapper );
    }
    setTimeout( function() {removeLoader()}, 1500 );
}

// 6. метод возвращает товары посредствам ajax запроса
function returnGoodsViaAjax( ajaxResult ) {
    let post = {};
    post['action'] = "get-catalog-products";
    post['sessid'] = BX.bitrix_sessid();
    if( !ajaxInProcces ) {
        $.ajax({
            type: "POST",
            data: post,
            url: ajaxUrl,
            datatype: "json",
            async: false,
            success: function( data ){
                ajaxResult = JSON.parse( data );
                ajaxInProcces = false;
            }
        });
    }
    let goodsHtml = "";
    for( let i=0; i<ajaxResult.length; i++ ) {
        goodsHtml += "<div class='catalog-item' data-id='" + ajaxResult[i]['ID'] + "'>";
            goodsHtml += ajaxResult[i]['NAME']
        goodsHtml += "</div>";
    }
    return goodsHtml;
}

// 7. добавить loader
function addLoader( obj ) {
    let loaderWrapper = document.createElement("div");
    loaderWrapper.classList.add('small-loader');
    loaderWrapper.innerHTML = "<img src='/local/components/devconsult/call.deal/templates/.default/images/loader.svg' />";
    obj.parentNode.appendChild( loaderWrapper );
}

// 8. убрать loader
function removeLoader() {
    let loaderWrapper = document.querySelector(".small-loader");
    if( loaderWrapper.parentNode !== null ) {
        loaderWrapper.parentNode.removeChild( loaderWrapper );
    }
}

// 9. удалить список с товарами
function removeGoodsWrapper() {
    if( document.querySelector(".goods-wrapper") ) {
        let goodsWrapper = document.querySelector(".goods-wrapper");
        goodsWrapper.parentNode.removeChild( goodsWrapper );
    }
}

// 10. поиск товара в input
function findGood( currentInput ) {
    let goodsItems = document.querySelectorAll(".catalog-item");
    for( let item in goodsItems ) {
        if( goodsItems[item].innerText?.length > 0 ) {
            if( !goodsItems[item].innerText.toLowerCase().includes(currentInput.value.toLowerCase()) ) {
                goodsItems[item].classList.add("catalog-item__invisible");
            } else {
                goodsItems[item].classList.remove("catalog-item__invisible");
            }
        } else {
            goodsItems[item].classList?.remove("catalog-item__invisible");
        }
    }
}

// 11. добавление товара в список, перерасчет сумм, скидок, общей суммы
function addGoodIntoBasket( id, ajaxResult ) {
    // сделаем ajax запрос и получим цену товара
    let post = {};
    post['id'] = id;
    post['action'] = "get-catalog-item";
    post['sessid'] = BX.bitrix_sessid();
    $.ajax({
        type: "POST",
        data: post,
        url: ajaxUrl,
        datatype: "json",
        async: false,
        success: function( data ){
            ajaxResult = JSON.parse( data );
        }
    });
    return ajaxResult;
}

// 12. метод пересчитывает данные строки при изменении одного из input этой строки
function reCalculateTrRow( currentTr, identifier ) {
    // возможные атрибуты: data-price, data-count, data-salesumm(сумма скидки), data-summ (общая сумма по строке-товара)
    let currentPrice = parseFloat(  currentTr.querySelector("[data-price]").value ) > 0 ? parseFloat(  currentTr.querySelector("[data-price]").value ) : 0;
    let currentCount = parseInt( currentTr.querySelector("[data-count]").value ) > 0 ? parseInt( currentTr.querySelector("[data-count]").value ) : 0;
    let currentSaleSumm = parseInt( currentTr.querySelector("[data-salesumm]").value ) > 0 ? parseInt( currentTr.querySelector("[data-salesumm]").value ) : 0;
    let currentSumm = parseFloat(  currentTr.querySelector("[data-summ]").value ) > 0 ? parseFloat(  currentTr.querySelector("[data-summ]").value ) : 0;

    if( event.target.hasAttribute("data-summ") ) {
        // изменена сумма строки по товару
        currentTr.querySelector("[data-salesumm]").value = parseInt( currentPrice * currentCount - currentSumm );
    } else {
        // если изменяются другие поля
        currentTr.querySelector("[data-summ]").value = parseInt( (currentCount * currentPrice ) - currentSaleSumm );
    }

    calculateFullSumm( identifier );
}

// 13. метод рассчитывает полную сумму всех товаров и помещает в нужный DOM элемент
function calculateFullSumm( identifier ) {
    let fullSumm = 0;
    let form = document.querySelector(".deal-form-" + identifier);
    let allGoodsFullSumm = form.querySelectorAll("[data-summ]");

    if( allGoodsFullSumm.length > 0 ) {
        for( let i=0; i<allGoodsFullSumm.length; i++ ) {
            let currentSumm = parseFloat( allGoodsFullSumm[i].value );
            fullSumm = fullSumm + currentSumm;
        }
    }

    // если общая сумма не является числом, то подставляем 0
    if( !isNaN( fullSumm ) ) {
        $(".deal-form__sum-span_" + identifier).text( fullSumm );
    } else {
        $(".deal-form__sum-span_" + identifier).text( 0 );
    }
}

// 14. метод создает сделку при создании товара или обновляет сделку
function createDeal( identifier ) {
    let formidentifier = document.querySelector(".deal-form__itself_" + identifier);
    let data = new FormData( formidentifier );
    if(window.XMLHttpRequest) {
        ajaxRequest = new XMLHttpRequest();
    } else {
        ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
    }

    ajaxRequest.open( 'post', ajaxUrl, false );
    ajaxRequest.send( data );

    if ( ajaxRequest.readyState === 4 && ajaxRequest.status === 200 ) {
        let dealIdentifier = JSON.parse(ajaxRequest.response)['DEAL_ID'];
        let summaryMesage = ""
        // 1) Добавим идентификатор сделки в форму
        let identifierInput = $("input[name='dealidentifier']");
        if( $(identifierInput).val() > 0 ) {
            summaryMesage = "Сделка успешно обновлена";
        } else {
            summaryMesage = "Сделка успешно создана";
            $(identifierInput).val( dealIdentifier );
        }

        // 2) Удалим часы preloader с кнопки
        $(".ui-btn-success_custom").removeClass("ui-btn-clock");

        // 3) добавим список документов в форму, которые созданы в результате добавления товаров в сделку
        let documents = JSON.parse(ajaxRequest.response)['DOCUMENTS'];
        let products = JSON.parse(ajaxRequest.response)['PRODUCTS'];
        newProductRowsCreation ( products, documents, dealIdentifier );

        // 4) Теперь еще нам нужно вывести так же документы для редактирования
        addNewDocumentRows( products, documents, dealIdentifier );

        let popupIdentifer = 'success-deal-created-' + dealIdentifier + "__" + (Math.random() + 1).toString(36).substring(7);
        let successDealCreatedPopup = new BX.PopupWindow(
            popupIdentifer,
            null,
            {
                width: 400,
                height: 200,
                closeByEsc: true,
                closeIcon: true,
                titleBar: 'Уведомление',
                content: '<h3 class="success-message">' + summaryMesage + '</h3>'
            }
        );
        successDealCreatedPopup.show();
    } else {
        alert( 'При создании сделки произошла ошибка' );
    }
}

// 15. метод добавляет новую строку с документом
function addNewDocumentRows( products, documents, dealIdentifier ) {
    let dealDocuments = ".documents__wrapper_" + dealIdentifier;
    $(dealDocuments).html("");

    for(let j=0; j<documents.length; j++ ) {

        let currentGoodName = documents[j]['NAME'];
        let currentGoodId;

        for( let m=0; m<products.length; m++ ) {
            if( products[m]['PRODUCT_NAME'] === currentGoodName ) {
                currentGoodId = products[m]['PRODUCT_ID'];
            }
        }

        let htmlData = "<div class='document-created document-created__" + currentGoodId + "'>";
                htmlData += "<div title='" + currentGoodName + "' class='document-created__inner'>";
                    htmlData += "<div data-link='/services/lists/32/element/0/" + documents[j]['ID'] + "/?external_context=creatingElementFromCrm' class='document-created__open'><img src='/local/components/devconsult/call.deal/templates/.default/images/down.svg' />Редактировать</div>";
                    htmlData += "<div class='document-created__name'><b>Шаблон: </b>" + currentGoodName + "</div>";
                    htmlData += "<div class='document-created__id'><b>ID шаблона: </b>" + documents[j]['ID'] + "</div>";
                    htmlData += "<div class='document-created__edited document-created__edited_hidden'><img width='24px' src='/local/components/devconsult/call.deal/templates/.default/images/checked.svg' /></div>";
                htmlData += "</div>";
            htmlData += "</div>";
        $(".documents__wrapper_" + dealIdentifier).append( htmlData );
    }
}

// 16. метод добоавляет новую строку с товаром типа MetroStation
function newProductRowsCreation ( products, documents, formid ) {

    // 1) проверим если нет thead, то тогда создадим его
    let dealFormClass = ".deal-form-" + formid;
    let dealForm = document.querySelector( dealFormClass );
    let dealFormGroupProductsCurrentForm = "deal-form__group_products_" + formid;
    
    if( products.length > 0 ) {
        if( !( dealForm.querySelector("thead") ) ) {
            let tHead = BX.create(
                "thead",
                {
                    "children" : [
                        BX.create(
                            "tr",
                            {
                                "children": [
                                    BX.create("th",{ text: "Товар" }),
                                    BX.create("th",{ text: "Цена (руб)" }),
                                    BX.create("th",{ text: "Количество (шт)" }),
                                    BX.create("th",{ text: "Сумма скидки" }),
                                    BX.create("th",{ text: "Сумма (руб)" }),
                                    BX.create("th",{ text: "" }),
                                    BX.create("th",{ text: "" }),
                                ]
                            }
                        )
                    ]
                }
            );
            $("." + dealFormGroupProductsCurrentForm + " table").append( tHead );
        }
    }


    // 2) очистим полностью tbody текущей формы, чтобы записать в нее товары, полученные из базы данных после создания или обновления сделки
    let currentTbody = $("." + dealFormGroupProductsCurrentForm + " tbody");
    $(currentTbody).html("");

    for( let p=0; p<products.length; p++ ) {
        // добавим новую строчку товара
        
        
        let fullSummForRow = (parseFloat( products[p].PRICE_BRUTTO ) * parseFloat( products[p].QUANTITY )) - (parseFloat(products[p].DISCOUNT_SUM)*parseFloat(products[p].QUANTITY));

        let newRow =
            BX.create(
                "tr",
                {
                    "children": [
                        BX.create(
                            'td',
                            {
                                html: '<input data-identifier="' + formid + '" autocomplete="off" name="goods[]" value="' + products[p].PRODUCT_NAME + '" data-good type="text" class="ui-ctl-element ui-ctl-element_custom" placeholder="Товар">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<input data-identifier="' + formid + '" autocomplete="off" name="prices[]" value="' + parseFloat(products[p].PRICE_BRUTTO) + '" data-price type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Цена (руб)">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<input data-identifier="' + formid + '" autocomplete="off" name="quantity[]" value="' + parseFloat(products[p].QUANTITY) + '" data-count type="number" class="ui-ctl-element ui-ctl-element_custom" placeholder="Количество (шт)">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<input data-identifier="' + formid + '" autocomplete="off" name="sale[]" value="' + parseFloat(products[p].DISCOUNT_SUM) * parseFloat(products[p].QUANTITY) + '" data-salesumm type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Скидка">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<input data-identifier="' + formid + '" autocomplete="off" value="' + fullSummForRow + '" data-summ type="number" step="0.01" class="ui-ctl-element ui-ctl-element_custom" placeholder="Сумма (руб)">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<input autocomplete="off" name="ids[]" value="' + products[p]['PRODUCT_ID'] + '" data-goodid type="hidden">'
                            }
                        ),
                        BX.create(
                            'td',
                            {
                                html: '<button data-productname="' + products[p]['PRODUCT_ID'] + '" data-identifier="' + formid + '" data-delete title="Удалить этот товар">x</span>'
                            }
                        )
                    ]
                }
            );
        // Добавляем новые товары к текущей форме

        $(currentTbody).append( newRow );
    }

    

    // 3) запустим перерасчет всей суммы еще раз
    calculateFullSumm( formid );
}

// 17. метод удаляет документы удаленного товара
function removeDocumentsOfCurrentProduct( productid, formId ) {
	if( document.querySelector(".documents__wrapper_" + formId) != null ) {
		let documents = document.querySelector(".documents__wrapper_" + formId).querySelectorAll(".document-created");
    	$(".documents__wrapper_" + formId + " .document-created__" + productid).remove();
	}
}

// 18. управление товаром типа metrostation и зависимого от этого товара select
function handleMetroStation( metrostationValue, dealIdentifier  ) {
    
    let dealForm = document.querySelector(".deal-form__itself_" + dealIdentifier);
    let metroStationSelect = dealForm.querySelector("select[name='UF_CRM_METRO_STATION']");
    let metroStationOptions = metroStationSelect.querySelectorAll('option');
    for(let i=0; i<metroStationOptions.length; i++) {
        metroStationOptions[i].selected = false;
    }
}










// ОТРАБОТКА ПОИСКА АДРЕСА -------------------------------------
// при нажатии на элемент списка выбирается этот элемент в input
$(document).on("click",".address-item",function(){
    let dataCode = $(this).data('code');
    let dataCity = $(this).data('city');
    let dataSettlement = $(this).data('settlement');

    let fieldToBeField = $(this).parent().parent().find(".field-to-be-fill");

    $(fieldToBeField).val($(this).text());
    $(fieldToBeField).data("code",dataCode);
    $(fieldToBeField).data("city",dataCity);
    $(fieldToBeField).data("settlement",dataSettlement);

    $(".addresses-results").fadeOut(50);
});
let inProcess = false;
let typingTymer;
$(document).on("keyup","input.house",function(){
    clearTimeout(typingTymer);
    let identifier = this.dataset.identifier
    typingTymer = setTimeout( getHouse( identifier ) ,1000);
});
$(document).on("keydown","input.field-to-be-fill",function(){
    clearTimeout(typingTymer);
});
$(document).on("click",function(){
    $(".addresses-results").html("").fadeOut(50);
});
function getHouse( identifier ) {
    if(!inProcess){
        BX.showWait();
        inProcess = true;
        let dataCode = $("input.city").data('code');
        let dataCity = $("input.city").data('city');
        let dataSettlement = $("input.city").data('settlement');
        let valueOfCurrentField = $("input.house_" + identifier).val();

        let restriction = {
            dataCity:dataCity,
            dataSettlement:dataSettlement
        };

        $.ajax({
            url:dadataAjaxUrl,
            type:"POST",
            dataType:"JSON",
            data:{
                dadata:"Y",
                getHouse:"Y",
                query:valueOfCurrentField,
                restriction:restriction
            },
            success:function( result ){
                let houses = "";
                for(let key in result){
                    houses += "<div data-zip='" + result[key]['postal_code'] + "' data-geolon='" + result[key]['geo_lon'] + "' data-geolat='" + result[key]['geo_lat'] + "' data-value='" + result[key]['value'] + "' class='address-item'>" + result[key]['value'] + "</div>";
                }
                $(".house-results-" + identifier).html(houses).fadeIn(50);
                inProcess = false;
                BX.closeWait();
            }
        });
    }
    return true;
}

