import Component from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import LinkedAccount from '../models/LinkedAccount';
interface IAttrs {
    user: User;
}
interface IState {
    loadingAdditional: boolean;
    errorLoadingAdditional: boolean;
}
export default class LinkedAccounts extends Component<IAttrs, IState> {
    state: IState;
    oncreate(vnode: Mithril.VnodeDOM<IAttrs, this>): void;
    view(): Mithril.Children;
    linkedAccountsItems(linkedAccounts: LinkedAccount[], user: User): ItemList<Mithril.Children>;
    loadLinkedAccounts(): Promise<void>;
}
export {};
