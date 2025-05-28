import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type UserSecurityPage from 'flarum/forum/components/UserSecurityPage';
import ItemList from 'flarum/common/utils/ItemList';
import LinkedAccounts from '../components/LinkedAccounts';
import type Mithril from 'mithril';

export default function addLinkedAccountsToUserSecurityPage() {
  extend('flarum/forum/components/UserSecurityPage', 'settingsItems', function (this: UserSecurityPage, items: ItemList<Mithril.Children>) {
    if (this.user?.id() !== app.session.user?.id() && !app.forum.attribute('fofOauthModerate')) {
      return;
    }

    items.add('linkedAccounts', <LinkedAccounts user={this.user} />, 5);
  });
}
