export default class AuthSettingsPage extends ExtensionPage<import("flarum/admin/components/ExtensionPage").ExtensionPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    showing: any[] | undefined;
    content(): JSX.Element;
    providerSettingsItems(): ItemList<any>;
    getAvailableGroups(): import("flarum/common/Model").default[];
    customProviderSettings(name: any): ItemList<any>;
}
import ExtensionPage from "flarum/admin/components/ExtensionPage";
import ItemList from "flarum/common/utils/ItemList";
