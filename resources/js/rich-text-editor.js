export default () => ({
    enhanced: false,

    init() {
        this.$refs.editor.innerHTML = this.$refs.input.value;
        this.enhanced = true;
    },

    run(command, value = null) {
        this.$refs.editor.focus();
        document.execCommand(command, false, value);
        this.sync();
    },

    format(event) {
        const tag = event.target.value;

        this.run('formatBlock', tag === 'p' ? 'p' : tag);
        event.target.value = 'p';
    },

    addLink() {
        const url = window.prompt('Naar welke veilige URL moet de link verwijzen?');

        if (!url) {
            return;
        }

        if (!/^(https?:\/\/|mailto:|tel:|\/)/i.test(url.trim())) {
            window.alert('Gebruik een http(s)-, mailto-, tel- of relatieve URL.');

            return;
        }

        this.run('createLink', url.trim());
    },

    sync() {
        this.$refs.input.value = this.$refs.editor.innerHTML;
        this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
    },
});
