import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';
import type Group from 'flarum/common/models/Group';
import type Mithril from 'mithril';
export default class AuthSettingsPage extends ExtensionPage {
    showing: Record<string, boolean>;
    oninit(vnode: Mithril.Vnode<this['attrs'], this>): void;
    content(): JSX.Element;
    providerSettingsItems(): ItemList<Mithril.Children>;
    getAvailableGroups(): Group[];
    customProviderSettings(name: string): ItemList<Mithril.Children>;
}
