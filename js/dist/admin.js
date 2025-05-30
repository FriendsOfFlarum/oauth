/******/ (() => { // webpackBootstrap
/******/ 	// runtime can't be in strict mode because a global variable is assign and maybe created.
/******/ 	var __webpack_modules__ = ({

/***/ "./src/admin/components/AuthSettingsPage.js":
/*!**************************************************!*\
  !*** ./src/admin/components/AuthSettingsPage.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AuthSettingsPage)
/* harmony export */ });
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Dropdown */ "flarum/common/components/Dropdown");
/* harmony import */ var flarum_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/admin/components/ExtensionPage */ "flarum/admin/components/ExtensionPage");
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Icon */ "flarum/common/components/Icon");
/* harmony import */ var flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/utils/ItemList */ "flarum/common/utils/ItemList");
/* harmony import */ var flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__);






class AuthSettingsPage extends (flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default()) {
  oninit(vnode) {
    super.oninit(vnode);
    this.showing = [];
  }
  content() {
    return [m("div", {
      className: "container"
    }, m("div", {
      className: "AuthSettingsPage"
    }, m("form", {
      className: "Form"
    }, this.buildSettingComponent({
      type: 'boolean',
      setting: 'fof-oauth.only_icons',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.admin.settings.only_icons_label")
    }), this.buildSettingComponent({
      type: 'boolean',
      setting: 'fof-oauth.update_email_from_provider',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.update_email_from_provider_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.update_email_from_provider_help')
    }), this.buildSettingComponent({
      type: 'boolean',
      setting: 'fof-oauth.fullscreenPopup',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.fullscreen_popup_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.fullscreen_popup_help')
    }), this.buildSettingComponent({
      type: 'number',
      setting: 'fof-oauth.popupWidth',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.popup_width_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.popup_width_help'),
      placeholder: 580,
      min: 0
    }), this.buildSettingComponent({
      type: 'number',
      setting: 'fof-oauth.popupHeight',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.popup_height_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.popup_height_help'),
      placeholder: 400,
      min: 0
    }), m("hr", null), this.providerSettingsItems().toArray(), m("hr", null), m("div", {
      className: "AuthSettingsPage--advanced"
    }, m("h4", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.advanced.heading')), this.buildSettingComponent({
      type: 'boolean',
      setting: 'fof-oauth.log-oauth-errors',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.advanced.log-oauth-errors-label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.advanced.log-oauth-errors-help')
    })), this.submitButton())))];
  }
  providerSettingsItems() {
    const items = new (flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5___default())();
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().data['fof-oauth'].map(provider => {
      const {
        name
      } = provider;
      const enabled = !!Number(this.setting("fof-oauth.".concat(name))());
      const showSettings = !!this.showing[name];
      const callbackUrl = "".concat(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('baseUrl'), "/auth/").concat(name);
      items.add("fof-oauth.".concat(name), m("div", {
        className: "Provider ".concat(enabled ? 'enabled' : 'disabled', " ").concat(showSettings && 'showing')
      }, m("div", {
        className: "Provider--info Provider--".concat(name)
      }, this.buildSettingComponent({
        type: 'boolean',
        setting: "fof-oauth.".concat(name),
        label: m("div", null, m((flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4___default()), {
          name: provider.icon
        }), m("span", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.lib.providers.".concat(name))))
      }), m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1___default()), {
        className: "Button Button--rounded ".concat(this.showing[name] && 'active'),
        onclick: () => this.showing[name] = !showSettings,
        "aria-label": flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings_accessibility_label', {
          name
        })
      }, m((flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4___default()), {
        name: "fas fa-cog"
      }))), m("div", {
        className: "Provider--settings",
        inert: !showSettings
      }, m("div", null, m("p", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.admin.settings.providers.".concat(name, ".description"), {
        link: m("a", {
          href: provider.link,
          target: "_blank"
        }, provider.link)
      })), m("p", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.admin.settings.providers.callback_url_text", {
        url: m("a", {
          href: callbackUrl,
          target: "_blank"
        }, callbackUrl)
      })), Object.keys(provider.fields).map(field => this.buildSettingComponent({
        type: 'string',
        setting: "fof-oauth.".concat(name, ".").concat(field),
        label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans("fof-oauth.admin.settings.providers.".concat(name, ".").concat(field, "_label")),
        required: {
          [showSettings && provider.fields[field].includes('required') ? 'required' : null]: true
        }
      })), this.customProviderSettings(name).toArray()))));
    });
    return items;
  }
  getAvailableGroups() {
    const groups = flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().store.all('groups');
    return groups.filter(group => !['2', '3'].includes(group.id())); // Exclude the "Guests" and "Members" groups
  }
  customProviderSettings(name) {
    const items = new (flarum_common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5___default())();

    // Add group selection dropdown
    items.add('group', m("div", {
      className: "Form-group"
    }, m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.providers.group_label')), m("div", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.providers.group_help')), (() => {
      const groupId = this.setting("fof-oauth.".concat(name, ".group"))();
      const selectedGroup = groupId ? flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().store.getById('groups', groupId) : null;
      const icons = {
        1: 'fas fa-check',
        // Admins
        3: 'fas fa-user',
        // Members
        4: 'fas fa-map-pin' // Mods
      };
      return m((flarum_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_2___default()), {
        label: selectedGroup ? [m((flarum_common_components_Icon__WEBPACK_IMPORTED_MODULE_4___default()), {
          name: selectedGroup.icon() || icons[selectedGroup.id()]
        }), '\t', selectedGroup.namePlural()] : flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.providers.no_group_label'),
        buttonClassName: "Button",
        disabled: !this.setting("fof-oauth.".concat(name))()
      }, m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1___default()), {
        icon: "fas fa-times",
        onclick: () => this.setting("fof-oauth.".concat(name, ".group"))(''),
        active: !groupId
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.settings.providers.no_group_label')), this.getAvailableGroups().map(group => m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_1___default()), {
        icon: group.icon() || icons[group.id()],
        onclick: () => this.setting("fof-oauth.".concat(name, ".group"))(group.id()),
        active: groupId === group.id(),
        key: group.id()
      }, group.namePlural())));
    })()));
    return items;
  }
}
flarum.reg.add('fof-oauth', 'admin/components/AuthSettingsPage', AuthSettingsPage);

/***/ }),

/***/ "./src/admin/components/ConfigureWithOAuthButton.js":
/*!**********************************************************!*\
  !*** ./src/admin/components/ConfigureWithOAuthButton.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ConfigureWithOAuthButton)
/* harmony export */ });
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/LinkButton */ "flarum/common/components/LinkButton");
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_1__);


class ConfigureWithOAuthButton extends (flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_1___default()) {
  view() {
    return [m((flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_1___default()), {
      className: "Button",
      href: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().route('extension', {
        id: 'fof-oauth'
      })
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.configure_button_label'))];
  }
}
flarum.reg.add('fof-oauth', 'admin/components/ConfigureWithOAuthButton', ConfigureWithOAuthButton);

/***/ }),

/***/ "./src/admin/components/ConfigureWithOAuthPage.js":
/*!********************************************************!*\
  !*** ./src/admin/components/ConfigureWithOAuthPage.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ConfigureWithOAuthPage)
/* harmony export */ });
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/components/ExtensionPage */ "flarum/admin/components/ExtensionPage");
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _ConfigureWithOAuthButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ConfigureWithOAuthButton */ "./src/admin/components/ConfigureWithOAuthButton.js");



/**
 * The `ConfigureWithOAuthPage` component is meant for 3rd party extensions to provide a handy link to `fof/oauth` settings.
 * It is not used directly by `fof/oauth` itself.
 */
class ConfigureWithOAuthPage extends (flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_0___default()) {
  oninit(vnode) {
    super.oninit(vnode);
  }
  content() {
    return [m("div", {
      className: "container"
    }, m("div", {
      className: "OAuthSettingsPage"
    }, m("br", null), m(_ConfigureWithOAuthButton__WEBPACK_IMPORTED_MODULE_1__["default"], null)))];
  }
}
flarum.reg.add('fof-oauth', 'admin/components/ConfigureWithOAuthPage', ConfigureWithOAuthPage);

/***/ }),

/***/ "./src/admin/extend.js":
/*!*****************************!*\
  !*** ./src/admin/extend.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extenders */ "flarum/common/extenders");
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extenders__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_AuthSettingsPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/AuthSettingsPage */ "./src/admin/components/AuthSettingsPage.js");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ([new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_1___default().Admin)().page(_components_AuthSettingsPage__WEBPACK_IMPORTED_MODULE_2__["default"]).permission(() => ({
  icon: 'fas fa-sign-in-alt',
  label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('fof-oauth.admin.permissions.moderate_user_providers'),
  permission: 'moderateUserProviders'
}), 'moderate')]);

/***/ }),

/***/ "./src/admin/index.js":
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AuthSettingsPage: () => (/* reexport safe */ _components_AuthSettingsPage__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   ConfigureWithOAuthButton: () => (/* reexport safe */ _components_ConfigureWithOAuthButton__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   ConfigureWithOAuthPage: () => (/* reexport safe */ _components_ConfigureWithOAuthPage__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   extend: () => (/* reexport safe */ _extend__WEBPACK_IMPORTED_MODULE_3__["default"])
/* harmony export */ });
/* harmony import */ var _components_AuthSettingsPage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/AuthSettingsPage */ "./src/admin/components/AuthSettingsPage.js");
/* harmony import */ var _components_ConfigureWithOAuthPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/ConfigureWithOAuthPage */ "./src/admin/components/ConfigureWithOAuthPage.js");
/* harmony import */ var _components_ConfigureWithOAuthButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/ConfigureWithOAuthButton */ "./src/admin/components/ConfigureWithOAuthButton.js");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./extend */ "./src/admin/extend.js");






/***/ }),

/***/ "flarum/admin/app":
/*!******************************************************!*\
  !*** external "flarum.reg.get('core', 'admin/app')" ***!
  \******************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'admin/app');

/***/ }),

/***/ "flarum/admin/components/ExtensionPage":
/*!***************************************************************************!*\
  !*** external "flarum.reg.get('core', 'admin/components/ExtensionPage')" ***!
  \***************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'admin/components/ExtensionPage');

/***/ }),

/***/ "flarum/common/components/Button":
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Button')" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Button');

/***/ }),

/***/ "flarum/common/components/Dropdown":
/*!***********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Dropdown')" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Dropdown');

/***/ }),

/***/ "flarum/common/components/Icon":
/*!*******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Icon')" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Icon');

/***/ }),

/***/ "flarum/common/components/LinkButton":
/*!*************************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/LinkButton')" ***!
  \*************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/LinkButton');

/***/ }),

/***/ "flarum/common/extenders":
/*!*************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extenders')" ***!
  \*************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/extenders');

/***/ }),

/***/ "flarum/common/utils/ItemList":
/*!******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/ItemList')" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/ItemList');

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
  !*** ./admin.js ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AuthSettingsPage: () => (/* reexport safe */ _src_admin__WEBPACK_IMPORTED_MODULE_0__.AuthSettingsPage),
/* harmony export */   ConfigureWithOAuthButton: () => (/* reexport safe */ _src_admin__WEBPACK_IMPORTED_MODULE_0__.ConfigureWithOAuthButton),
/* harmony export */   ConfigureWithOAuthPage: () => (/* reexport safe */ _src_admin__WEBPACK_IMPORTED_MODULE_0__.ConfigureWithOAuthPage),
/* harmony export */   extend: () => (/* reexport safe */ _src_admin__WEBPACK_IMPORTED_MODULE_0__.extend)
/* harmony export */ });
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.js");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=admin.js.map