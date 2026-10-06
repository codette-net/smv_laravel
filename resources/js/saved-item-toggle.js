export default (config) => ({
    saved: config.saved,
    pending: false,
    failed: false,
    saveUrl: config.saveUrl,
    unsaveUrl: config.unsaveUrl,
    saveLabel: config.saveLabel,
    unsaveLabel: config.unsaveLabel,

    get label() {
        return this.saved ? this.unsaveLabel : this.saveLabel;
    },

    async toggle() {
        if (this.pending) {
            return;
        }

        this.pending = true;
        this.failed = false;

        const formData = new FormData(this.$el);
        formData.set('_method', this.saved ? 'DELETE' : 'POST');

        try {
            const response = await fetch(this.saved ? this.unsaveUrl : this.saveUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Save request failed with status ${response.status}.`);
            }

            const result = await response.json();
            this.saved = result.saved === true;
        } catch {
            this.failed = true;
        } finally {
            this.pending = false;
        }
    },
});
