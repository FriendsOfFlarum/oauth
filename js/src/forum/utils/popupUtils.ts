import type ForumApplication from 'flarum/forum/ForumApplication';

export function openOAuthPopup(app: ForumApplication, attrs: Record<string, any>) {
  const fullscreen = app.forum.attribute('fof-oauth.fullscreenPopup');
  if (fullscreen) {
    window.open(app.forum.attribute('baseUrl') + attrs.path, 'logInPopup', 'fullscreen=yes');
  } else {
    const defaultWidth = 580;
    const defaultHeight = 400;

    const width = app.forum.attribute<number>('fof-oauth.popupWidth') || defaultWidth;
    const height = app.forum.attribute<number>('fof-oauth.popupHeight') || defaultHeight;

    const windowHeight = $(window).height() ?? 0;
    const windowWidth = $(window).width() ?? 0;

    const top = windowHeight / 2 - height / 2;
    const left = windowWidth / 2 - width / 2;

    window.open(
      app.forum.attribute('baseUrl') + attrs.path,
      'logInPopup',
      `width=${width},` + `height=${height},` + `top=${top},` + `left=${left},` + 'status=no,scrollbars=yes,resizable=no'
    );
  }
}
