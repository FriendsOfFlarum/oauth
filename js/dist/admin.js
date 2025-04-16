(()=>{var t={n:o=>{var n=o&&o.__esModule?()=>o.default:()=>o;return t.d(n,{a:n}),n},d:(o,n)=>{for(var e in n)t.o(n,e)&&!t.o(o,e)&&Object.defineProperty(o,e,{enumerable:!0,get:n[e]})},o:(t,o)=>Object.prototype.hasOwnProperty.call(t,o),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},o={};(()=>{"use strict";t.r(o),t.d(o,{AuthSettingsPage:()=>b,ConfigureWithOAuthButton:()=>y,ConfigureWithOAuthPage:()=>P});const n=flarum.core.compat["admin/app"];var e=t.n(n);function a(t,o){return a=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,o){return t.__proto__=o,t},a(t,o)}function r(t,o){t.prototype=Object.create(o.prototype),t.prototype.constructor=t,a(t,o)}const i=flarum.core.compat["common/components/Button"];var s=t.n(i);const l=flarum.core.compat["common/components/Dropdown"];var u=t.n(l);const f=flarum.core.compat["admin/components/ExtensionPage"];var p=t.n(f);const d=flarum.core.compat["common/helpers/icon"];var c=t.n(d);const h=flarum.core.compat["common/utils/ItemList"];var g=t.n(h),b=function(t){function o(){return t.apply(this,arguments)||this}r(o,t);var n=o.prototype;return n.oninit=function(o){t.prototype.oninit.call(this,o),this.showing=[]},n.content=function(){return[m("div",{className:"container"},m("div",{className:"AuthSettingsPage"},m("div",{className:"Form"},this.buildSettingComponent({type:"boolean",setting:"fof-oauth.only_icons",label:e().translator.trans("fof-oauth.admin.settings.only_icons_label")}),this.buildSettingComponent({type:"boolean",setting:"fof-oauth.update_email_from_provider",label:e().translator.trans("fof-oauth.admin.settings.update_email_from_provider_label"),help:e().translator.trans("fof-oauth.admin.settings.update_email_from_provider_help")}),this.buildSettingComponent({type:"boolean",setting:"fof-oauth.fullscreenPopup",label:e().translator.trans("fof-oauth.admin.settings.fullscreen_popup_label"),help:e().translator.trans("fof-oauth.admin.settings.fullscreen_popup_help")}),this.buildSettingComponent({type:"number",setting:"fof-oauth.popupWidth",label:e().translator.trans("fof-oauth.admin.settings.popup_width_label"),help:e().translator.trans("fof-oauth.admin.settings.popup_width_help"),placeholder:580,min:0}),this.buildSettingComponent({type:"number",setting:"fof-oauth.popupHeight",label:e().translator.trans("fof-oauth.admin.settings.popup_height_label"),help:e().translator.trans("fof-oauth.admin.settings.popup_height_help"),placeholder:400,min:0}),m("hr",null),this.providerSettingsItems().toArray(),m("hr",null),m("div",{className:"AuthSettingsPage--advanced"},m("h4",null,e().translator.trans("fof-oauth.admin.settings.advanced.heading")),this.buildSettingComponent({type:"boolean",setting:"fof-oauth.log-oauth-errors",label:e().translator.trans("fof-oauth.admin.settings.advanced.log-oauth-errors-label"),help:e().translator.trans("fof-oauth.admin.settings.advanced.log-oauth-errors-help")})),this.submitButton())))]},n.providerSettingsItems=function(){var t=this,o=new(g());return e().data["fof-oauth"].map((function(n){var a=n.name,r=!!Number(t.setting("fof-oauth."+a)()),i=!!t.showing[a],l=e().forum.attribute("baseUrl")+"/auth/"+a;o.add("fof-oauth."+a,m("div",{className:"Provider "+(r?"enabled":"disabled")+" "+(i&&"showing")},m("div",{className:"Provider--info Provider--"+a},t.buildSettingComponent({type:"boolean",setting:"fof-oauth."+a,label:m("div",null,c()(n.icon),m("span",null,e().translator.trans("fof-oauth.lib.providers."+a)))}),m(s(),{className:"Button Button--rounded "+(t.showing[a]&&"active"),onclick:function(){return t.showing[a]=!i},"aria-label":e().translator.trans("fof-oauth.admin.settings_accessibility_label",{name:a})},c()("fas fa-cog"))),m("div",{className:"Provider--settings"},m("div",null,m("p",null,e().translator.trans("fof-oauth.admin.settings.providers."+a+".description",{link:m("a",{href:n.link,target:"_blank"},n.link)})),m("p",null,e().translator.trans("fof-oauth.admin.settings.providers.callback_url_text",{url:m("a",{href:l,target:"_blank"},l)})),Object.keys(n.fields).map((function(o){var r;return t.buildSettingComponent({type:"string",setting:"fof-oauth."+a+"."+o,label:e().translator.trans("fof-oauth.admin.settings.providers."+a+"."+o+"_label"),required:(r={},r[i&&n.fields[o].includes("required")?"required":null]=!0,r)})})),t.customProviderSettings(a).toArray()))))})),o},n.getAvailableGroups=function(){return e().store.all("groups").filter((function(t){return!["2","3"].includes(t.id())}))},n.customProviderSettings=function(t){var o,n,a,r=this,i=new(g());return i.add("group",m("div",{className:"Form-group"},m("label",null,e().translator.trans("fof-oauth.admin.settings.providers.group_label")),m("div",{className:"helpText"},e().translator.trans("fof-oauth.admin.settings.providers.group_help")),(n=(o=r.setting("fof-oauth."+t+".group")())?e().store.getById("groups",o):null,a={1:"fas fa-check",3:"fas fa-user",4:"fas fa-map-pin"},m(u(),{label:n?[c()(n.icon()||a[n.id()]),"\t",n.namePlural()]:e().translator.trans("fof-oauth.admin.settings.providers.no_group_label"),buttonClassName:"Button",disabled:!r.setting("fof-oauth."+t)()},m(s(),{icon:"fas fa-times",onclick:function(){return r.setting("fof-oauth."+t+".group")("")},active:!o},e().translator.trans("fof-oauth.admin.settings.providers.no_group_label")),r.getAvailableGroups().map((function(n){return m(s(),{icon:n.icon()||a[n.id()],onclick:function(){return r.setting("fof-oauth."+t+".group")(n.id())},active:o===n.id(),key:n.id()},n.namePlural())})))))),i},o}(p());const v=flarum.core.compat["common/components/LinkButton"];var _=t.n(v),y=function(t){function o(){return t.apply(this,arguments)||this}return r(o,t),o.prototype.view=function(){return[m(_(),{className:"Button",href:e().route("extension",{id:"fof-oauth"})},e().translator.trans("fof-oauth.admin.configure_button_label"))]},o}(_()),P=function(t){function o(){return t.apply(this,arguments)||this}r(o,t);var n=o.prototype;return n.oninit=function(o){t.prototype.oninit.call(this,o)},n.content=function(){return[m("div",{className:"container"},m("div",{className:"OAuthSettingsPage"},m("br",null),m(y,null)))]},o}(p());e().initializers.add("fof/oauth",(function(){e().extensionData.for("fof-oauth").registerPage(b).registerPermission({icon:"fas fa-sign-in-alt",label:e().translator.trans("fof-oauth.admin.permissions.moderate_user_providers"),permission:"moderateUserProviders"},"moderate")}))})(),module.exports=o})();
//# sourceMappingURL=admin.js.map