/**
 * The `ConfigureWithOAuthPage` component is meant for 3rd party extensions to provide a handy link to `fof/oauth` settings.
 * It is not used directly by `fof/oauth` itself.
 */
export default class ConfigureWithOAuthPage extends ExtensionPage<import("flarum/admin/components/ExtensionPage").ExtensionPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    content(): JSX.Element[];
}
import ExtensionPage from "flarum/admin/components/ExtensionPage";
