import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Avatar from 'flarum/common/components/Avatar';
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
  className() {
    return 'Modal--small AccountLinkedModal';
  }

  title() {
    return app.translator.trans('fof-oauth.forum.account_linked.title');
  }

  content(): Mithril.Children {
    const user = app.session.user!;
    const providerKey = this.attrs.provider;
    const providerName = app.translator.trans(`fof-oauth.forum.providers.${providerKey}`);

    return (
      <div className="Modal-body">
        <div className="AccountLinkedModal-user" style="text-align: center; margin-bottom: 1rem;">
          <Avatar user={user} style="display: block; margin: 0 auto 0.5rem;" />
          <span className="AccountLinkedModal-username" style="display: block; font-weight: bold;">
            {user.displayName()}
          </span>
        </div>
        <p className="AccountLinkedModal-body">
          {app.translator.trans('fof-oauth.forum.account_linked.body', {
            forum: app.forum.attribute<string>('title'),
            username: user.displayName(),
            provider: providerName,
          })}
        </p>
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" onclick={() => this.hide()}>
            {app.translator.trans('fof-oauth.forum.account_linked.dismiss_button')}
          </Button>
        </div>
      </div>
    );
  }
}
