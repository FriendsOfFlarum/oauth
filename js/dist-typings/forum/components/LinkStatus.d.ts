import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LinkedAccount from '../models/LinkedAccount';
import User from 'flarum/common/models/User';
export interface ILinkStatusAttrs extends ComponentAttrs {
    provider: LinkedAccount;
    user: User;
}
export interface LinkStatusState {
    loading: boolean;
}
export default class LinkStatus extends Component<ILinkStatusAttrs, LinkStatusState> {
    state: {
        loading: boolean;
    };
    onbeforeupdate(vnode: Mithril.Vnode<ILinkStatusAttrs, this>): void;
    view(): Mithril.Children;
    iconView(): JSX.Element;
    statusView(): JSX.Element;
    actionView(): JSX.Element | null;
    deleteProvider(provider: LinkedAccount): Promise<void>;
}
