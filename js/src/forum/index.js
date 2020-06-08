import { extend } from 'flarum/extend';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';
import ItemList from 'flarum/utils/ItemList';
import extractText from 'flarum/utils/extractText';

app.initializers.add('fof/oauth', () => {
    const onlyIcons = !!Number(app.data['fof-oauth.only_icons']);

    let buttons = null;

    extend(LogInButtons.prototype, 'items', function (items) {
        if (!buttons) {
            buttons = new ItemList();

            app.forum
                .attribute('fof-oauth')
                .filter(Boolean)
                .forEach(({ name, icon }) =>
                    buttons.add(
                        name,
                        <LogInButton className={`Button LogInButton--${name}`} icon={icon} path={`/auth/${name}`}>
                            {app.translator.trans(`fof-oauth.forum.log_in.with_button`, {
                                provider: app.translator.trans(`fof-oauth.lib.providers.${name}`),
                            })}
                        </LogInButton>
                    )
                );
        }

        items.merge(buttons);
    });

    if (onlyIcons) {
        extend(LogInButton, 'initProps', function (nul, props) {
            if (!props.children) return;

            props.title = extractText(props.children);
            props.children = '';
        });

        extend(LogInButton.prototype, 'config', function (nul, isInitialized) {
            if (isInitialized) return;

            this.$().tooltip({ container: '#modal' });
        });

        extend(LogInButtons.prototype, 'view', function (vdom) {
            vdom.attrs.className += ' LogInButtons--icons';
        });
    }
});
