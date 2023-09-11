import app from 'flarum/forum/app';
import addLinkedAccountsToUserSecurityPage from './extend/addLinkedAccountsToUserSecurityPage';
import extendLoginSignup from './extend/extendLoginSignup';
import LinkedAccount from './models/LinkedAccount';

app.initializers.add('fof/oauth', () => {
  app.store.models['linked-accounts'] = LinkedAccount;

  extendLoginSignup();
  addLinkedAccountsToUserSecurityPage();
});
