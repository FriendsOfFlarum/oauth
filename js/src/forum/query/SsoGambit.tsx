import app from 'flarum/forum/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';

export default class SsoGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('fof-oauth.lib.gambits.sso.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('fof-oauth.lib.gambits.sso.hint', {}, true);
  }

  filterKey(): string {
    return 'sso';
  }

  enabled(): boolean {
    return !!app.forum.attribute('fofOauthModerate');
  }
}
