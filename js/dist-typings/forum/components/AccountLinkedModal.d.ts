import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface IAccountLinkedModalAttrs extends IInternalModalAttrs {
    /** The provider name as stored in login_providers.provider, e.g. 'github'. */
    provider: string;
}
/**
 * Shown after a user's forum account is linked to an OAuth provider for the
 * first time — both via the security-page manual link flow and the automatic
 * email-match link that happens during login.
 */
export default class AccountLinkedModal extends Modal<IAccountLinkedModalAttrs> {
    className(): string;
    title(): string | any[];
    content(): Mithril.Children;
}
