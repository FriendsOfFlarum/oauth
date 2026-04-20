import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
/**
 * The `ConfigureWithOAuthPage` component is meant for 3rd party extensions to provide a handy link to `fof/oauth` settings.
 * It is not used directly by `fof/oauth` itself.
 */
export default class ConfigureWithOAuthPage extends ExtensionPage {
    oninit(vnode: Mithril.Vnode<this['attrs'], this>): void;
    content(): JSX.Element;
}
