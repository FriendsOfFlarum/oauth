import AuthSettingsModal from './components/AuthSettingsModal';

app.initializers.add('fof/oauth', () => {
    app.extensionSettings['fof-oauth'] = () => app.modal.show(AuthSettingsModal);
});
