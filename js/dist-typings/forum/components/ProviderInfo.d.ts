import Component from 'flarum/common/Component';
import type LinkedAccount from '../models/LinkedAccount';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
interface IProviderInfoAttrs {
    provider: LinkedAccount;
}
export default class ProviderInfo extends Component<IProviderInfoAttrs> {
    view(_vnode: Mithril.Vnode<IProviderInfoAttrs, this>): Mithril.Children;
    providerInfoItems(provider: LinkedAccount): ItemList<Mithril.Children>;
}
export {};
