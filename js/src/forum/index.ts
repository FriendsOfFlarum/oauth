import app from 'flarum/forum/app';
import addLinkedAccountsToUserSecurityPage from './extenders/addLinkedAccountsToUserSecurityPage';
import extendLoginSignup from './extenders/extendLoginSignup';

export { default as extend } from './extend';

app.initializers.add('fof/oauth', () => {
  extendLoginSignup();
  addLinkedAccountsToUserSecurityPage();

  // Detect the _flarum_auth query parameter added by ResponseFactory after a
  // new-user OAuth callback. Strip it from the URL and open the SignUpModal.
  const params = new URLSearchParams(window.location.search);
  const authToken = params.get('_flarum_auth');

  if (authToken) {
    // Remove the param from the URL without triggering a page reload.
    params.delete('_flarum_auth');
    const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', clean);

    // Defer until after app.mount() has run so the modal manager is mounted.
    setTimeout(() => {
      app.modal.show(() => import('flarum/forum/components/SignUpModal'), { token: authToken });
    }, 0);
  }
});
