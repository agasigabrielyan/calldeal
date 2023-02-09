<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $APPLICATION
 * @var $templateFolder
 * @var $arParams
 * @var $arResult
 */
use Bitrix\Main\Config\Option;
\Bitrix\Main\UI\Extension::load("ui.forms");
$APPLICATION->SetTitle('Новая заявка');
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<div class="deal-form deal-form-<?= $arParams['IDENTIFIER']; ?>">
    <form class="deal-form__itself_<?= $arParams['IDENTIFIER']; ?>">
        <input autocomplete="off" type="hidden" name="action" value="create-deal" />
        <input autocomplete="off" type="hidden" name="dealidentifier" value="<?= $arParams['IDENTIFIER'] ?>" />
        <?= bitrix_sessid_post(); ?>
        <div class="deal-form__group">
            <h2><a target="_blank" href="/crm/deal/details/<?= $arParams['IDENTIFIER']; ?>/">Заявка (<?= $arParams['IDENTIFIER']; ?>)</a></h2>
            <div>
                <a class="deal-form__button <?= $arResult['DEAL_DATA']['STAGE_ID'] === "C1:18" ? "" : "deal-form__button_opacitied" ?> deal-form__button_modification ui-btn ui-btn-danger">
                    Доработка
                    <input
                            <?= $arResult['DEAL_DATA']['STAGE_ID'] === "C1:18" ? "checked" : "" ?>
                            type="radio"
                            name="STAGE_RADIO"
                            value="C1:18">
                </a>
                <a class="deal-form__button <?= $arResult['DEAL_DATA']['STAGE_ID'] === "C1:20" ? "" : "deal-form__button_opacitied" ?> deal-form__button_non-targeted ui-btn ui-btn-hover">
                    Нецелевой
                    <input
                            <?= $arResult['DEAL_DATA']['STAGE_ID'] === "C1:20" ? "checked" : "" ?>
                            type="radio"
                            name="STAGE_RADIO"
                            value="C1:20">
                </a>
            </div>
            <div class="deal-form__full-sum">
                Общая сумма:
                <span class="deal-form__sum-span deal-form__sum-span_<?= $arParams['IDENTIFIER'] ?>">0 </span>руб
            </div>
        </div>
        <div class="deal-form__group">
            <label>
                ФИО
                <span class="ui-ctl ui-ctl-textbox deal-form__input-area">
                    <input
                            autocomplete="off"
                            name="name"
                            type="text"
                            class="ui-ctl-element"
                            placeholder="ФИО"
                            value="<?= strlen($arResult['DEAL_DATA']['CRM_DEAL_contacttable_FULL_NAME'])>0 ? $arResult['DEAL_DATA']['CRM_DEAL_contacttable_FULL_NAME'] : ''?>" />
                    <input
                            type="hidden"
                            name="CONTACT_ID"
                            value="<?= strlen($arResult['DEAL_DATA']['CONTACT_ID'])>0 ? $arResult['DEAL_DATA']['CONTACT_ID'] : ''?>" />
                </span>
            </label>
            <label>
                Комментарий
                <span class="ui-ctl ui-ctl-textbox">
                    <input
                            autocomplete="off"
                            name="COMMENTS"
                            type="text"
                            class="ui-ctl-element"
                            placeholder="Комментарий"
                            value="<?= strlen( $arResult['DEAL_DATA']['COMMENTS'] )>0 ? $arResult['DEAL_DATA']['COMMENTS'] : '' ?>" />
                </span>
            </label>
        </div>
        <div class="deal-form__group">
            <h3 class="deal-form__h3">Добавить товар</h3>
            <a class="ui-btn ui-btn-primary add-new-products-row" data-formid="<?= $arParams['IDENTIFIER'] ?>">
                Добавить товар
            </a>
        </div>
        <div class="deal-form__group deal-form__group_products deal-form__group_products_<?= $arParams['IDENTIFIER'] ?>">
            <table class="table">
                <tbody>
                    <? if( count($arResult['PRODUCTS'])>0 ): ?>
                        <? foreach( $arResult['PRODUCTS'] as $key => $value ): ?>
                            <?php
                                $fullSummForRow = (floatVal($value['PRICE_BRUTTO']) * floatVal($value['QUANTITY'])) - (floatVal($value['DISCOUNT_SUM']) * floatVal($value['QUANTITY']));
                            ?>
                            <tr>
                                <td>
                                    <input
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            autocomplete="off"
                                            name="goods[]"
                                            value="<?= $value['PRODUCT_NAME'] ?>"
                                            data-good=""
                                            type="text"
                                            class="ui-ctl-element ui-ctl-element_custom"
                                            placeholder="Товар">
                                </td>
                                <td>
                                    <input
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            autocomplete="off"
                                            name="prices[]"
                                            value="<?= $value['PRICE_BRUTTO'] ?>"
                                            data-price=""
                                            type="number"
                                            step="0.01"
                                            class="ui-ctl-element ui-ctl-element_custom"
                                            placeholder="Цена (руб)">
                                </td>
                                <td>
                                    <input
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            autocomplete="off"
                                            name="quantity[]"
                                            value="<?= $value['QUANTITY'] ?>"
                                            data-count=""
                                            type="number"
                                            class="ui-ctl-element ui-ctl-element_custom"
                                            placeholder="Количество (шт)">
                                </td>
                                <td>
                                    <input
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            autocomplete="off"
                                            name="sale[]"
                                            value="<?= ($value['DISCOUNT_SUM'] * $value['QUANTITY']) ?>"
                                            data-salesumm=""
                                            type="number"
                                            step="0.01"
                                            class="ui-ctl-element ui-ctl-element_custom"
                                            placeholder="Скидка">
                                </td>
                                <td>
                                    <input
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            autocomplete="off"
                                            value="<?= $fullSummForRow; ?>"
                                            data-summ=""
                                            type="number"
                                            step="0.01"
                                            class="ui-ctl-element ui-ctl-element_custom"
                                            placeholder="Сумма (руб)">
                                </td>
                                <td>
                                    <input
                                            autocomplete="off"
                                            name="ids[]"
                                            value="<?= $value['PRODUCT_ID'] ?>"
                                            data-goodid=""
                                            type="hidden">
                                </td>
                                <td>
                                    <button
                                            <?= strlen($value['METRO_STATION'])>0 ? 'data-metrostation = ' . $value['METRO_STATION'] : '' ?>
                                            data-productid="<?= $value['PRODUCT_ID'] ?>"
                                            data-identifier="<?= $arParams['IDENTIFIER']; ?>"
                                            data-delete=""
                                            title="Удалить этот товар">x</button>
                                </td>
                            </tr>
                        <? endforeach; ?>
                    <? endif; ?>
                </tbody>
                <? if( count($arResult['PRODUCTS']) > 0 ): ?>
                    <thead><tr><th>Товар</th><th>Цена (руб)</th><th>Количество (шт)</th><th>Сумма скидки</th><th>Сумма (руб)</th><th></th><th></th></tr></thead>
                <? endif; ?>
            </table>
        </div>
        <!-- BEGIN: documents -->
        <div class="documents__wrapper documents__wrapper_<?= $arParams['IDENTIFIER'] ?>">
            <? if( count($arResult['DOCUMENTS']) > 0 ): ?>
                <? foreach( $arResult['DOCUMENTS'] as $key => $value): ?>
                    <?
                        foreach( $arResult['PRODUCTS'] as $productKey => $productValue ) {
                            if( $productValue['PRODUCT_NAME'] === $value['NAME']) {
                                $currentGoodId = $productValue['PRODUCT_ID'];
                            }
                        }
                    ?>
                    <div class="document-created document-created__<?= $currentGoodId ?>">
                        <div title="<?= $value['NAME'] ?>" class="document-created__inner">
                            <div data-link="/services/lists/32/element/0/<?= $value['ID'] ?>/?external_context=creatingElementFromCrm" class="document-created__open"><img src="/local/components/devconsult/call.deal/templates/.default/images/down.svg">Редактировать</div>
                            <div class="document-created__name"><b>Шаблон: </b><?= $value['NAME'] ?></div>
                            <div class="document-created__id"><b>ID шаблона: </b><?= $value['ID'] ?></div>
                            <div class="document-created__edited document-created__edited_hidden"><img width="24px" src="/local/components/devconsult/call.deal/templates/.default/images/checked.svg"></div>
                        </div>
                    </div>
                <?endforeach; ?>
            <? endif; ?>
        </div>
        <!-- end: documents -->
        <div class="deal-form__group deal-form__group_bottom-form">
            <label>
                <span>[O]</span> Нужен ли скан?
                <span class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
                    <select name="UF_CRM_1592552787" class="ui-ctl-element">
                        <option <?= $arResult['DEAL_DATA']['UF_CRM_1592552787'] == "0" ? 'selected' : '' ?>     value="0">      Не выбрано</option>
                        <option <?= $arResult['DEAL_DATA']['UF_CRM_1592552787'] == "98" ? 'selected' : '' ?>    value="98">     Нет</option>
                        <option <?= $arResult['DEAL_DATA']['UF_CRM_1592552787'] == "99" ? 'selected' : '' ?>    value="99">     Скан+доставка</option>
                        <option <?= $arResult['DEAL_DATA']['UF_CRM_1592552787'] == "100" ? 'selected' : '' ?>   value="100">    Только скан</option>
                    </select>
                </span>
            </label>
            <label>

                <?php
                    // определим есть ли среди продуктов станция метро, если есть установим значение метро как selected
                    foreach( $arResult['PRODUCTS'] as $key => $value ) {
                        if( strlen($value['METRO_STATION'])>0 ) {
                            $metroStationValue = $value['METRO_STATION'];
                        }
                    }
                ?>
                <span>[O]</span> Станция метро и Доставка ТК
                <span class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
                    <select data-identifier="<?= $arParams['IDENTIFIER'] ?>" name="UF_CRM_METRO_STATION" class="ui-ctl-element">
                        <option <?= $metroStationValue == "0"     ? 'selected' : '' ?> value="0">--Не выбрано--</option>
                        <option <?= $metroStationValue == "7268"  ? 'selected' : '' ?> value="7268">< Доставка 1й класс ></option>
                        <option <?= $metroStationValue == "7269"  ? 'selected' : '' ?> value="7269">< Доставка EMS ></option>
                        <option <?= $metroStationValue == "9694"  ? 'selected' : '' ?> value="9694">< Доставка Почта России ></option>
                        <option <?= $metroStationValue == "7262"  ? 'selected' : '' ?> value="7262">< Доставка СДЭК ></option>
                        <option <?= $metroStationValue == "19143" ? 'selected' : '' ?> value="19143">< МОСКВА - САМОВЫВОЗ ></option>
                        <option <?= $metroStationValue == "18592" ? 'selected' : '' ?> value="18592">< САПСАН - САМОВЫВОЗ ></option>
                        <option <?= $metroStationValue == "5578"  ? 'selected' : '' ?> value="5578">Автово ПИТЕР</option>
                        <option <?= $metroStationValue == "375"   ? 'selected' : '' ?> value="375">Киевская</option>
                        <option <?= $metroStationValue == "427"   ? 'selected' : '' ?> value="427">Охотный ряд</option>
                        <option <?= $metroStationValue == "317"   ? 'selected' : '' ?> value="317">❌ Авиамоторная</option>
                    </select>
                </span>
            </label>
        </div>
        <div class="deal-form__group deal-form__group_bottom-form">
            <label>
                Срочная доставка
                <span class="ui-ctl ui-ctl-textbox deal-form__input-area">
                    <input
                            autocomplete="off"
                            name="UF_CRM_1591011159"
                            type="text"
                            class="ui-ctl-element"
                            value="<?= strlen( $arResult['DEAL_DATA']['UF_CRM_1591011159'] )>0 ? $arResult['DEAL_DATA']['UF_CRM_1591011159'] : '' ?>" />
                </span>
            </label>
            <label>
                Адрес доставки
                <span class="ui-ctl ui-ctl-textbox deal-form__input-area">
                    <div data-sid="HOUSE" style="width: 100%;">
                        <div class="animated-labels">
                            <div class="input">
                                <input
                                        style="width: 100%;"
                                        placeholder="Адрес доставки"
                                        data-zip=""
                                        data-settlement=""
                                        data-city=""
                                        data-code=""
                                        autocomplete="off"
                                        type="text"
                                        id="POPUP_HOUSE_<?= $arParams['IDENTIFIER'] ?>"
                                        name="UF_CRM_ADRES_DOSTAVKI"
                                        data-identifier="<?= $arParams['IDENTIFIER'] ?>"
                                        class="house form-control required field-to-be-fill house_<?= $arParams['IDENTIFIER'] ?>"
                                        value="<?= strlen( $arResult['DEAL_DATA']['UF_CRM_ADRES_DOSTAVKI'] )>0 ? $arResult['DEAL_DATA']['UF_CRM_ADRES_DOSTAVKI'] : '' ?>"
                                        aria-required="true"
                                >
                                <div class="addresses-results house-results house-results-<?= $arParams['IDENTIFIER'] ?>"></div>
                            </div>
                        </div>
                    </div>
                </span>
            </label>
        </div>
        <div class="deal-form__group deal-form__group_bottom-form">
            <label>
                Ответственный за контакт - ФИО получателя
                <span class="ui-ctl ui-ctl-textbox deal-form__input-area">
                    <input
                            autocomplete="off"
                            name="UF_CRM_RESPONSIBLE_CONTACT_NAME"
                            type="text"
                            class="ui-ctl-element"
                            placeholder="ФИО получателя"
                            value="<?= strlen( $arResult['DEAL_DATA']['UF_CRM_RESPONSIBLE_CONTACT_NAME'] )>0 ? $arResult['DEAL_DATA']['UF_CRM_RESPONSIBLE_CONTACT_NAME'] : '' ?>" />
                </span>
            </label>
            <label>
                Ответственный за контакт - Телефон получателя
                <span class="ui-ctl ui-ctl-textbox deal-form__input-area">
                    <input
                            autocomplete="off"
                            onkeydown="javascript: return ['Backspace','Delete','ArrowLeft','ArrowRight'].includes(event.code) ? true : !isNaN(Number(event.key)) && event.code!=='Space'"
                            name="UF_CRM_RESPONSIBLE_CONTACT_PHONE"
                            type="number"
                            class="ui-ctl-element"
                            placeholder="Телефон получателя"
                            value="<?= strlen( $arResult['DEAL_DATA']['UF_CRM_RESPONSIBLE_CONTACT_PHONE'] )>0 ? $arResult['DEAL_DATA']['UF_CRM_RESPONSIBLE_CONTACT_PHONE'] : '' ?>" />
                </span>
            </label>
        </div>
        <div class="deal-form__group deal-form__group_save">
            <div class="ui-entity-section ui-entity-section-control-edit-mode">
                <div data-identifier="<?= $arParams['IDENTIFIER'] ?>" class="ui-btn ui-btn-success ui-btn-success_custom">Сохранить</div>
                <!--<a href="#" class="ui-btn ui-btn-link">Отменить</a>-->
            </div>
        </div>
    </form>
</div>
