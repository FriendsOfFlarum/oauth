import app from 'flarum/forum/app';
import addLinkedAccountsToUserSecurityPage from './extenders/addLinkedAccountsToUserSecurityPage';
import extendLoginSignup from './extenders/extendLoginSignup';

export { default as extend } from './extend';

app.initializers.add('fof/oauth', () => {
  extendLoginSignup();
  addLinkedAccountsToUserSecurityPage();

  // Detect the _flarum_auth query parameter added by ResponseFactory after a
  // new-user OAuth callback. Strip it from the URL, fetch the token data from
  // the API to pre-populate the SignUpModal, then open the modal.
  const params = new URLSearchParams(window.location.search);
  const authToken = params.get('_flarum_auth');

  if (authToken) {
    // Remove the param from the URL without triggering a page reload.
    params.delete('_flarum_auth');
    const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', clean);

    // Defer until after app.mount() has run so the modal manager is mounted,
    // then resolve the token to username/email/provided before showing the modal.
    setTimeout(async () => {
      let modalAttrs: Record<string, unknown> = { token: authToken };

      try {
        const response = await app.request<{ username?: string; email?: string; provided?: string[] }>({
          method: 'POST',
          url: app.forum.attribute<string>('apiUrl') + '/registration-token',
          body: { token: authToken },
        });
        modalAttrs = {
          token: authToken,
          username: response?.username ?? '',
          email: response?.email ?? '',
          provided: response?.provided ?? [],
        };
      } catch {
        // If the fetch fails (e.g. token already used), fall back to opening
        // the modal with just the token — it will show empty fields.
      }

      app.modal.show(() => import('flarum/forum/components/SignUpModal'), modalAttrs);
    }, 0);
  }
});
