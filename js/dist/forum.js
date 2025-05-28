/******/ (() => { // webpackBootstrap
/******/ 	// runtime can't be in strict mode because a global variable is assign and maybe created.
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(e, r, t) {
  return (r = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPrimitive)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPropertyKey)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}


/***/ }),

/***/ "./src/forum/components/LinkStatus.tsx":
/*!*********************************************!*\
  !*** ./src/forum/components/LinkStatus.tsx ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkStatus)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _ProviderInfo__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ProviderInfo */ "./src/forum/components/ProviderInfo.tsx");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/Icon */ "flarum/common/components/Icon");
/* harmony import */ var flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_7__);








class LinkStatus extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "state", {
      loading: false
    });
  }
  onbeforeupdate(vnode) {
    super.onbeforeupdate(vnode);
    if ((flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingInProgress) && (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingProvider) === this.attrs.provider.name()) {
      this.state.loading = true;
    } else if ((flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingInProgress) === false && (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingProvider) === this.attrs.provider.name()) {
      this.state.loading = false;
      delete (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingInProgress);
      delete (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().fof_oauth_linkingProvider);
    }
  }
  view(vnode) {
    return m("div", {
      className: "LinkedAccounts-Account LinkedAccounts-Account--".concat(this.attrs.provider.name())
    }, this.iconView(), this.statusView(), this.actionView());
  }
  iconView() {
    return m("div", {
      className: "LinkedAccountsList-item-icon"
    }, m((flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_7___default()), {
      name: this.attrs.provider.icon(),
      className: "Provider-Icon Provider-Icon--".concat(this.attrs.provider.name())
    }));
  }
  statusView() {
    const provider = this.attrs.provider;
    return m(_ProviderInfo__WEBPACK_IMPORTED_MODULE_5__["default"], {
      provider: provider
    });
  }
  actionView() {
    var _app$session$user;
    const provider = this.attrs.provider;
    const user = this.attrs.user;
    if (provider.linked()) {
      return m("div", {
        className: "LinkedAccountsList-item-actions"
      }, m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default()), {
        className: "Button FoFLogInButton LogInButton--".concat(provider.name(), " LogInButton").concat(provider.linked() ? '--linked' : '--unlinked'),
        icon: provider.icon(),
        onclick: () => this.deleteProvider(provider),
        loading: this.state.loading
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-oauth.forum.unlink')));
    } else if (!provider.orphaned() && (user.id() === ((_app$session$user = (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session).user) == null ? void 0 : _app$session$user.id()) || !flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('fofOauthModerate'))) {
      return m("div", {
        className: "LinkedAccountsList-item-actions"
      }, m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_4___default()), {
        className: "Button FoFLogInButton LogInButton--".concat(provider.name()),
        icon: provider.icon(),
        href: "".concat(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('baseUrl'), "/auth/").concat(provider.name(), "?linkTo=").concat(user.id()),
        loading: this.state.loading,
        external: true
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans("fof-oauth.forum.log_in.with_".concat(provider.name(), "_button"), {
        provider: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans("fof-oauth.forum.providers.".concat(provider.name()))
      })));
    }
    return null;
  }
  async deleteProvider(provider) {
    if (confirm(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6___default()(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-oauth.forum.user.settings.linked-account.unlink-confirm', {
      provider: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans("fof-oauth.forum.providers.".concat(provider.name()))
    })))) {
      this.state.loading = true;
      await provider.delete();
      await flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().store.find('users/' + this.attrs.user.id() + '/linked-accounts', {});
      this.state.loading = false;
      m.redraw();
    }
  }
}
flarum.reg.add('fof-oauth', 'forum/components/LinkStatus', LinkStatus);

/***/ }),

/***/ "./src/forum/components/LinkedAccounts.tsx":
/*!*************************************************!*\
  !*** ./src/forum/components/LinkedAccounts.tsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkedAccounts)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/FieldSet */ "flarum/common/components/FieldSet");
/* harmony import */ var flarum_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/helpers/listItems */ "flarum/common/helpers/listItems");
/* harmony import */ var flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/utils/ItemList */ "flarum/common/utils/ItemList");
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _LinkStatus__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./LinkStatus */ "./src/forum/components/LinkStatus.tsx");








class LinkedAccounts extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "state", {
      loadingAdditional: true,
      errorLoadingAdditional: false
    });
  }
  oncreate(vnode) {
    super.oncreate(vnode);
    this.loadLinkedAccounts();
  }
  view(vnode) {
    const linkedAccounts = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().store.all('linked-accounts');
    return m((flarum_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_3___default()), {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-oauth.forum.user.settings.linked-account.label')
    }, m("p", {
      className: "helpText"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('fof-oauth.forum.user.settings.linked-account.help')), this.state.loadingAdditional ? m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_6___default()), {
      containerClassName: "LinkedAccounts-Loading"
    }) : m("ul", {
      className: "LinkedAccounts-List"
    }, flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4___default()(this.linkedAccountsItems(linkedAccounts, this.attrs.user).toArray())));
  }
  linkedAccountsItems(linkedAccounts, user) {
    const items = new (flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5___default())();
    linkedAccounts.forEach(linkedAccount => {
      items.add(linkedAccount.name(), m(_LinkStatus__WEBPACK_IMPORTED_MODULE_7__["default"], {
        provider: linkedAccount,
        user: user
      }), linkedAccount.priority());
    });
    return items;
  }
  async loadLinkedAccounts() {
    await flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().store.find('users/' + this.attrs.user.id(), {
      include: 'linkedAccounts'
    });
    // await app.store.find<LinkedAccount[]>('users/' + this.attrs.user.id() + '/linked-accounts', {});
    this.state.loadingAdditional = false;
    m.redraw();
  }
}
flarum.reg.add('fof-oauth', 'forum/components/LinkedAccounts', LinkedAccounts);

/***/ }),

/***/ "./src/forum/components/ProviderInfo.tsx":
/*!***********************************************!*\
  !*** ./src/forum/components/ProviderInfo.tsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ProviderInfo)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/helpers/humanTime */ "flarum/common/helpers/humanTime");
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/ItemList */ "flarum/common/utils/ItemList");
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__);




class ProviderInfo extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_1___default()) {
  oncreate(vnode) {
    super.oncreate(vnode);
  }
  view(vnode) {
    const {
      provider
    } = this.attrs;
    if (provider.orphaned()) {
      return m("div", null, m("p", {
        className: "LinkedAccountsList-item-title"
      }, provider.name()), m("p", {
        className: "helpText"
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.forum.user.settings.linked-account.orphaned-account')), m("div", {
        className: "LinkedAccountsList"
      }, this.providerInfoItems(provider).toArray()));
    }
    if (provider.linked()) {
      return m("div", null, m("p", {
        className: "LinkedAccountsList-item-title"
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.forum.providers.".concat(provider.name()))), m("div", {
        className: "LinkedAccountsList"
      }, this.providerInfoItems(provider).toArray()));
    }
    return m("div", null, m("p", {
      className: "LinkedAccountsList-item-title"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.forum.providers.".concat(provider.name()))));
  }
  providerInfoItems(provider) {
    const LabelValue = flarum.reg.get('core', 'common/components/LabelValue');
    const items = new (flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3___default())();
    items.add('firstLogin', m(LabelValue, {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.forum.user.settings.linked-account.link-created-label'),
      value: flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_2___default()(provider.firstLogin())
    }), 100);
    items.add('lastLogin', m(LabelValue, {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.forum.user.settings.linked-account.last-used-label'),
      value: flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_2___default()(provider.lastLogin())
    }), 90);
    items.add('identification', m(LabelValue, {
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.forum.user.settings.linked-account.identification-label', {
        provider: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.forum.providers.".concat(provider.name()))
      }),
      value: provider.providerIdentifier()
    }), 80);
    return items;
  }
}
flarum.reg.add('fof-oauth', 'forum/components/ProviderInfo', ProviderInfo);

/***/ }),

/***/ "./src/forum/components/index.ts":
/*!***************************************!*\
  !*** ./src/forum/components/index.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   components: () => (/* binding */ components)
/* harmony export */ });
/* harmony import */ var _LinkStatus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LinkStatus */ "./src/forum/components/LinkStatus.tsx");
/* harmony import */ var _LinkedAccounts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LinkedAccounts */ "./src/forum/components/LinkedAccounts.tsx");
/* harmony import */ var _ProviderInfo__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ProviderInfo */ "./src/forum/components/ProviderInfo.tsx");



const components = {
  ProviderInfo: _ProviderInfo__WEBPACK_IMPORTED_MODULE_2__["default"],
  LinkStatus: _LinkStatus__WEBPACK_IMPORTED_MODULE_0__["default"],
  LinkedAccounts: _LinkedAccounts__WEBPACK_IMPORTED_MODULE_1__["default"]
};
flarum.reg.add('fof-oauth', 'forum/components', null);

/***/ }),

/***/ "./src/forum/extend.ts":
/*!*****************************!*\
  !*** ./src/forum/extend.ts ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extenders */ "flarum/common/extenders");
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_models_User__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/models/User */ "flarum/common/models/User");
/* harmony import */ var flarum_common_models_User__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_models_User__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _models_LinkedAccount__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./models/LinkedAccount */ "./src/forum/models/LinkedAccount.ts");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ([new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default().Model)((flarum_common_models_User__WEBPACK_IMPORTED_MODULE_1___default())) //
.attribute('loginProvider'), new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default().Store)() //
.add('linked-accounts', _models_LinkedAccount__WEBPACK_IMPORTED_MODULE_2__["default"])]);

/***/ }),

/***/ "./src/forum/extenders/addLinkedAccountsToUserSecurityPage.tsx":
/*!*********************************************************************!*\
  !*** ./src/forum/extenders/addLinkedAccountsToUserSecurityPage.tsx ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ addLinkedAccountsToUserSecurityPage)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_LinkedAccounts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/LinkedAccounts */ "./src/forum/components/LinkedAccounts.tsx");



function addLinkedAccountsToUserSecurityPage() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/components/UserSecurityPage', 'settingsItems', function (items) {
    var _this$user, _app$session$user;
    if (((_this$user = this.user) == null ? void 0 : _this$user.id()) !== ((_app$session$user = (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().session).user) == null ? void 0 : _app$session$user.id()) && !flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('fofOauthModerate')) {
      return;
    }
    items.add('linkedAccounts', m(_components_LinkedAccounts__WEBPACK_IMPORTED_MODULE_2__["default"], {
      user: this.user
    }), 5);
  });
}
flarum.reg.add('fof-oauth', 'forum/extenders/addLinkedAccountsToUserSecurityPage', addLinkedAccountsToUserSecurityPage);

/***/ }),

/***/ "./src/forum/extenders/extendLoginSignup.js":
/*!**************************************************!*\
  !*** ./src/forum/extenders/extendLoginSignup.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_LogInButtons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/LogInButtons */ "flarum/forum/components/LogInButtons");
/* harmony import */ var flarum_forum_components_LogInButtons__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_LogInButtons__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/components/LogInButton */ "flarum/forum/components/LogInButton");
/* harmony import */ var flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_popupUtils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/popupUtils */ "./src/forum/utils/popupUtils.ts");






// import type LogInModal from 'flarum/forum/components/LogInModal';
// import type SignUpModal from 'flarum/forum/components/SignUpModal';

/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3___default()), 'initAttrs', function (returnedValue, attrs) {
    attrs.onclick = function () {
      (0,_utils_popupUtils__WEBPACK_IMPORTED_MODULE_6__.openOAuthPopup)((flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default()), attrs);
    };
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_LogInButtons__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'items', function (items) {
    const onlyIcons = !!flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('fof-oauth.only_icons');
    const buttons = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('fof-oauth').filter(Boolean);
    const googleButton = buttons.splice(buttons.indexOf(buttons.find(b => b.name === 'google')), 1);
    buttons.concat(googleButton).forEach(_ref => {
      let {
        name,
        icon,
        priority
      } = _ref;
      let className = "Button FoFLogInButton LogInButton--".concat(name);

      // Google branding does not allow inline icon, so we'll keep the full button
      if (onlyIcons && name !== 'google') {
        className += ' Button--icon';
      }
      items.add(name, m("div", {
        className: "LogInButtonContainer LogInButtonContainer--".concat(name)
      }, m((flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3___default()), {
        className: className,
        icon: icon,
        path: "/auth/".concat(name),
        disabled: (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_loginInProgress)
      }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.forum.log_in.with_".concat(name, "_button"), {
        provider: flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.forum.providers.".concat(name))
      }))), priority);
    });
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.override)((flarum_forum_components_LogInButton__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'view', function (orig, vnode) {
    const onlyIcons = !!flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('fof-oauth.only_icons');
    if (!onlyIcons) return orig(vnode);
    const child = orig(vnode);
    return m((flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5___default()), {
      text: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(child.children[1])
    }, child);
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_LogInButtons__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'view', function (vdom) {
    const onlyIcons = !!flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('fof-oauth.only_icons');
    if (!onlyIcons) return;
    vdom.attrs.className += ' FoFLogInButtons--icons';
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/ForumApplication', 'authenticationComplete', function (_, payload) {
    if (payload.loggedIn) {
      (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_loginInProgress) = true;
      // This will automatically be reset, as authenticationComplete also triggers a window reload.

      m.redraw();
    }
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.override)('flarum/forum/ForumApplication', 'authenticationComplete', async function () {
    try {
      (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_linkingInProgress) = true;
      m.redraw();

      // Refresh the list of providers
      const newProviders = await this.store.find('linked-accounts');

      // The store will contain an old version of the login provider (unlinked) that has
      // another ID than the new one (linked). We need to delete that one from the store
      // so that the UI gets updated correctly.

      // Find the old provider (one that has the same name as one of the new providers & has providerIdentifier===null)
      const oldProvider = this.store.all('linked-accounts')
      // Find the providers that have not yet been linked (providerIdentifier is null)
      .filter(p => p.providerIdentifier() === null)
      // Match it with one of the newly fetched providers by name
      .find(p => newProviders.some(np => np.name() === p.name()));
      if (oldProvider) {
        delete this.store.data['linked-accounts'][oldProvider.id()];
      } else {
        window.location.reload();
        return;
      }

      // Refresh the session user
      await this.store.find('users', flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().session.user.id());
      (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_linkingInProgress) = false;
      m.redraw();
    } catch (error) {
      (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_linkingInProgress) = false;
      m.redraw();
    }
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/components/LogInModal', 'onbeforeupdate', function (vnode) {
    if ((flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_loginInProgress)) {
      this.loading = true;
    }
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/components/SignUpModal', 'onbeforeupdate', function (vnode) {
    if ((flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().fof_oauth_loginInProgress)) {
      this.loading = true;
    }
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/components/SignUpModal', 'fields', function (items) {
    // If a suggested username was not provided by the OAuth service, display some help text to the user.
    if (!!this.attrs.token && !!!this.attrs.username) {
      items.add('username-help', m("div", null, m("p", null, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.forum.signup.username_help'))), 35);
    }
    return items;
  });
}

/***/ }),

/***/ "./src/forum/index.ts":
/*!****************************!*\
  !*** ./src/forum/index.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   components: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_4__.components),
/* harmony export */   extend: () => (/* reexport safe */ _extend__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   utils: () => (/* reexport safe */ _utils__WEBPACK_IMPORTED_MODULE_5__.utils)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _extenders_addLinkedAccountsToUserSecurityPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./extenders/addLinkedAccountsToUserSecurityPage */ "./src/forum/extenders/addLinkedAccountsToUserSecurityPage.tsx");
/* harmony import */ var _extenders_extendLoginSignup__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./extenders/extendLoginSignup */ "./src/forum/extenders/extendLoginSignup.js");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./extend */ "./src/forum/extend.ts");
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components */ "./src/forum/components/index.ts");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./utils */ "./src/forum/utils/index.ts");






flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('fof/oauth', () => {
  (0,_extenders_extendLoginSignup__WEBPACK_IMPORTED_MODULE_2__["default"])();
  (0,_extenders_addLinkedAccountsToUserSecurityPage__WEBPACK_IMPORTED_MODULE_1__["default"])();
});

/***/ }),

/***/ "./src/forum/models/LinkedAccount.ts":
/*!*******************************************!*\
  !*** ./src/forum/models/LinkedAccount.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkedAccount)
/* harmony export */ });
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_0__);

class LinkedAccount extends (flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default()) {
  name() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('name').call(this);
  }
  icon() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('icon').call(this);
  }
  priority() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('priority').call(this);
  }
  linked() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('linked').call(this);
  }
  orphaned() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('orphaned').call(this);
  }
  identifier() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('identifier').call(this);
  }
  providerIdentifier() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('providerIdentifier').call(this);
  }
  firstLogin() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('firstLogin', (flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().transformDate)).call(this);
  }
  lastLogin() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().attribute('lastLogin', (flarum_common_Model__WEBPACK_IMPORTED_MODULE_0___default().transformDate)).call(this);
  }
}
flarum.reg.add('fof-oauth', 'forum/models/LinkedAccount', LinkedAccount);

/***/ }),

/***/ "./src/forum/utils/index.ts":
/*!**********************************!*\
  !*** ./src/forum/utils/index.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   utils: () => (/* binding */ utils)
/* harmony export */ });
/* harmony import */ var _popupUtils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./popupUtils */ "./src/forum/utils/popupUtils.ts");

const utils = {
  openOAuthPopup: _popupUtils__WEBPACK_IMPORTED_MODULE_0__.openOAuthPopup
};
flarum.reg.add('fof-oauth', 'forum/utils', null);

/***/ }),

/***/ "./src/forum/utils/popupUtils.ts":
/*!***************************************!*\
  !*** ./src/forum/utils/popupUtils.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   openOAuthPopup: () => (/* binding */ openOAuthPopup)
/* harmony export */ });
function openOAuthPopup(app, attrs) {
  const fullscreen = app.forum.attribute('fof-oauth.fullscreenPopup');
  if (fullscreen) {
    window.open(app.forum.attribute('baseUrl') + attrs.path, 'logInPopup', 'fullscreen=yes');
  } else {
    var _$$height, _$$width;
    const defaultWidth = 580;
    const defaultHeight = 400;
    const width = app.forum.attribute('fof-oauth.popupWidth') || defaultWidth;
    const height = app.forum.attribute('fof-oauth.popupHeight') || defaultHeight;
    const windowHeight = (_$$height = $(window).height()) != null ? _$$height : 0;
    const windowWidth = (_$$width = $(window).width()) != null ? _$$width : 0;
    const top = windowHeight / 2 - height / 2;
    const left = windowWidth / 2 - width / 2;
    window.open(app.forum.attribute('baseUrl') + attrs.path, 'logInPopup', "width=".concat(width, ",") + "height=".concat(height, ",") + "top=".concat(top, ",") + "left=".concat(left, ",") + 'status=no,scrollbars=yes,resizable=no');
  }
}
flarum.reg.add('fof-oauth', 'forum/utils/popupUtils', { openOAuthPopup: openOAuthPopup, });

/***/ }),

/***/ "flarum/common/Component":
/*!*************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/Component')" ***!
  \*************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/Component');

/***/ }),

/***/ "flarum/common/Model":
/*!*********************************************************!*\
  !*** external "flarum.reg.get('core', 'common/Model')" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/Model');

/***/ }),

/***/ "flarum/common/components/Button":
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Button')" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Button');

/***/ }),

/***/ "flarum/common/components/FieldSet":
/*!***********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/FieldSet')" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/FieldSet');

/***/ }),

/***/ "flarum/common/components/Icon":
/*!*******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Icon')" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Icon');

/***/ }),

/***/ "flarum/common/components/Link":
/*!*******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Link')" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Link');

/***/ }),

/***/ "flarum/common/components/LoadingIndicator":
/*!*******************************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/LoadingIndicator')" ***!
  \*******************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/LoadingIndicator');

/***/ }),

/***/ "flarum/common/components/Tooltip":
/*!**********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Tooltip')" ***!
  \**********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Tooltip');

/***/ }),

/***/ "flarum/common/extend":
/*!**********************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extend')" ***!
  \**********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/extend');

/***/ }),

/***/ "flarum/common/extenders":
/*!*************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extenders')" ***!
  \*************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/extenders');

/***/ }),

/***/ "flarum/common/helpers/humanTime":
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/helpers/humanTime')" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/helpers/humanTime');

/***/ }),

/***/ "flarum/common/helpers/listItems":
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/helpers/listItems')" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/helpers/listItems');

/***/ }),

/***/ "flarum/common/models/User":
/*!***************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/models/User')" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/models/User');

/***/ }),

/***/ "flarum/common/utils/ItemList":
/*!******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/ItemList')" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/ItemList');

/***/ }),

/***/ "flarum/common/utils/extractText":
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/extractText')" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/extractText');

/***/ }),

/***/ "flarum/forum/app":
/*!******************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/app')" ***!
  \******************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'forum/app');

/***/ }),

/***/ "flarum/forum/components/LogInButton":
/*!*************************************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/components/LogInButton')" ***!
  \*************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'forum/components/LogInButton');

/***/ }),

/***/ "flarum/forum/components/LogInButtons":
/*!**************************************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/components/LogInButtons')" ***!
  \**************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'forum/components/LogInButtons');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		flarum.reg._webpack_runtimes["fof-oauth"] ||= __webpack_require__;// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!******************!*\
  !*** ./forum.js ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   components: () => (/* reexport safe */ _src_forum__WEBPACK_IMPORTED_MODULE_0__.components),
/* harmony export */   extend: () => (/* reexport safe */ _src_forum__WEBPACK_IMPORTED_MODULE_0__.extend),
/* harmony export */   utils: () => (/* reexport safe */ _src_forum__WEBPACK_IMPORTED_MODULE_0__.utils)
/* harmony export */ });
/* harmony import */ var _src_forum__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/forum */ "./src/forum/index.ts");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=forum.js.map