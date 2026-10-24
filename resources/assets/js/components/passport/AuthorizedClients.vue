<style scoped>
.apps-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.apps-tabs .nav-link {
    cursor: pointer;
    padding: 0.25rem 0.75rem;
    font-size: 13px;
}

.apps-scroll {
    height: 420px;
    overflow-y: auto;
}

.apps-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #fff;
    border-top: 0;
    border-bottom: 0;
    box-shadow: inset 0 -1px 0 #dee2e6;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6c757d;
}

.apps-scroll tbody td {
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
}

.apps-scroll tbody tr:first-child td {
    border-top: 0;
}

.apps-scroll tbody tr:hover {
    background: #f8f9fa;
}

.apps-scroll tr.is-inactive .app-name {
    color: #6c757d;
}

.apps-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6c757d;
}
</style>

<template>
    <div>
        <div class="card card-default mb-4">
            <div class="card-header font-weight-bold bg-white">
                <div class="apps-header">
                    <span>Authorized Applications</span>

                    <ul class="nav nav-pills apps-tabs">
                        <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                            <a class="nav-link" :class="{ active: filter === tab.id }" @click="setFilter(tab.id)">
                                {{ tab.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="apps-scroll">
                    <!-- Loading -->
                    <div v-if="loading" class="apps-state">
                        <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                        Loading
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="apps-state">
                        <div class="text-center">
                            <p class="mb-2">Unable to load applications.</p>
                            <a class="btn btn-outline-secondary btn-sm font-weight-bold" @click="fetchTokens(true)">
                                Try again
                            </a>
                        </div>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="tokens.length === 0" class="apps-state">
                        {{ emptyMessage }}
                    </div>

                    <!-- Authorized Tokens -->
                    <template v-else>
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Scopes</th>
                                    <th>Expires</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="token in tokens" :key="token.id"
                                    :class="{ 'is-inactive': token.status !== 'active' }">
                                    <!-- Client Name -->
                                    <td>
                                        <div class="font-weight-bold app-name">
                                            {{ token.name }}
                                            <span class="badge ml-1" :class="getStatusBadge(token.status)">
                                                {{ token.status }}
                                            </span>
                                        </div>
                                        <small class="text-muted" v-if="token.created_at" style="font-size:12px;">
                                            Authorized {{ formatDate(token.created_at) }}
                                        </small>
                                    </td>

                                    <!-- Scopes -->
                                    <td>
                                        <template v-if="token.scopes && token.scopes.length > 0">
                                            <span v-for="scope in token.scopes" :key="scope" class="badge mr-1 mb-1"
                                                :class="getScopeBadge(scope)">
                                                {{ scope === '*' ? 'all scopes' : scope }}
                                            </span>
                                        </template>
                                        <span v-else class="text-muted">None</span>
                                    </td>

                                    <!-- Expires -->
                                    <td style="white-space: nowrap;">
                                        <span v-if="!token.expires_at" class="text-muted" style="font-size:12px;">
                                            Never
                                        </span>
                                        <span v-else :class="{ 'text-danger': token.status === 'expired' }"
                                            style="font-size:12px;">
                                            {{ formatDate(token.expires_at) }}
                                        </span>
                                    </td>

                                    <!-- Revoke Button -->
                                    <td class="text-right">
                                        <button v-if="token.status === 'active' && !token.current_session" type="button"
                                            class="btn btn-danger btn-sm font-weight-bold"
                                            :disabled="revoking === token.id" @click="revoke(token)">
                                            {{ revoking === token.id ? 'Revoking' : 'Revoke' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div v-if="nextCursor" class="text-center py-3 border-top">
                            <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold"
                                :disabled="loadingMore" @click="fetchTokens(false)">
                                {{ loadingMore ? 'Loading' : 'Load more' }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    /*
     * The component's data.
     */
    data() {
        return {
            tokens: [],
            filter: 'active',
            tabs: [
                { id: 'all', label: 'All' },
                { id: 'active', label: 'Active' },
                { id: 'revoked', label: 'Revoked' }
            ],
            limit: 20,
            nextCursor: null,
            loading: true,
            loadingMore: false,
            error: false,
            revoking: null,
            requestId: 0
        };
    },

    computed: {
        emptyMessage() {
            switch (this.filter) {
                case 'active':
                    return 'No active applications.';

                case 'revoked':
                    return 'No revoked or expired applications.';

                default:
                    return 'You have not authorized any applications.';
            }
        }
    },

    /**
     * Prepare the component (Vue 1.x).
     */
    ready() {
        this.prepareComponent();
    },

    /**
     * Prepare the component (Vue 2.x).
     */
    mounted() {
        this.prepareComponent();
    },

    methods: {
        /**
         * Prepare the component (Vue 2.x).
         */
        async prepareComponent() {
            try {
                await axios.get('/sanctum/csrf-cookie');
            } catch (e) {
                //
            }

            this.fetchTokens(true);
        },

        /**
         * Switch the active tab.
         */
        setFilter(filter) {
            if (this.filter === filter) {
                return;
            }

            this.filter = filter;
            this.fetchTokens(true);
        },

        /**
         * Fetch a page of tokens. Pass true to start over from the first page.
         */
        async fetchTokens(reset) {
            const requestId = ++this.requestId;

            if (reset) {
                this.tokens = [];
                this.nextCursor = null;
                this.loading = true;
            } else {
                this.loadingMore = true;
            }

            this.error = false;

            const params = {
                filter: this.filter,
                limit: this.limit
            };

            if (!reset && this.nextCursor) {
                params.cursor = this.nextCursor;
            }

            try {
                const response = await axios.get('/api/v1.1/accounts/apps-and-applications', { params });

                // Ignore responses from a tab the user has already left
                if (requestId !== this.requestId) {
                    return;
                }

                this.tokens = reset ? response.data : this.tokens.concat(response.data);
                this.nextCursor = this.parseNextCursor(response.headers.link);
            } catch (e) {
                if (requestId !== this.requestId) {
                    return;
                }

                if (reset) {
                    this.error = true;
                }
            }

            this.loading = false;
            this.loadingMore = false;
        },

        /**
         * Pull the next cursor out of the Link header.
         */
        parseNextCursor(link) {
            if (!link) {
                return null;
            }

            const match = link.match(/<([^>]+)>;\s*rel="next"/);

            if (!match) {
                return null;
            }

            try {
                return new URL(match[1], window.location.origin).searchParams.get('cursor');
            } catch (e) {
                return null;
            }
        },

        /**
         * Revoke the given token.
         */
        revoke(token) {
            this.revoking = token.id;

            axios.post('/api/v1.1/accounts/apps-and-applications/' + token.id + '/revoke')
                .then(response => {
                    if (this.filter === 'active') {
                        this.tokens = this.tokens.filter(t => t.id !== token.id);
                    } else {
                        this.tokens = this.tokens.map(t => t.id === token.id ? response.data : t);
                    }
                })
                .catch(() => {
                    swal('Error', 'Unable to revoke this application. Please try again.', 'error');
                })
                .then(() => {
                    this.revoking = null;
                });
        },

        /**
         * Get the badge class for the given status.
         */
        getStatusBadge(status) {
            switch (status) {
                case 'active':
                    return 'badge-success';

                case 'expired':
                    return 'badge-warning';

                default:
                    return 'badge-secondary';
            }
        },

        /**
         * Get the badge class for the given scope.
         */
        getScopeBadge(scope) {
            switch (scope) {
                case '*':
                    return 'badge-danger';

                case 'read':
                    return 'badge-secondary';

                case 'push':
                    return 'badge-info';

                case 'write':
                case 'follow':
                case 'admin:read':
                case 'admin:read:domain_blocks':
                case 'admin:write':
                case 'admin:write:domain_blocks':
                    return 'badge-danger';

                default:
                    return 'badge-light';
            }
        },

        /**
         * Format the given date.
         */
        formatDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);

            if (isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        }
    }
}
</script>
