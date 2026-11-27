import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { extend } from '../../../src/forum';

beforeAll(() => {
  bootstrapForum();
  app.bootExtensions({ 'fof-oauth': { extend } });
  app.boot();
  app.forum.pushAttributes({ fofOauthModerate: true });
  app.translator.addTranslations({
    'fof-oauth.lib.gambits.sso.key': '第三方登录',
  });
});

it('turns the translated gambit into the sso filter', () => {
  expect(app.search.gambits.apply('users', { q: '第三方登录:github' })).toStrictEqual({ q: '', sso: 'github' });
});

it('still turns the English sso: into the sso filter on a translated forum', () => {
  expect(app.search.gambits.apply('users', { q: 'sso:github' })).toStrictEqual({ q: '', sso: 'github' });
});

it('still turns the negated English -sso: into the negated filter on a translated forum', () => {
  expect(app.search.gambits.apply('users', { q: '-sso:github' })).toStrictEqual({ q: '', '-sso': 'github' });
});
