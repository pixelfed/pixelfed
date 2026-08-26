<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <div>
            <div class="card card-default mb-4">
                <div class="card-header font-weight-bold bg-white">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            Personal Access Tokens
                        </span>

                        <a class="action-link" tabindex="-1" @click="showCreateTokenForm">
                            Create New Token
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <p class="mb-0" v-if="tokens.length === 0">
                        You have not created any personal access tokens.
                    </p>

                    <table class="table table-borderless mb-0" v-if="tokens.length > 0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Scopes</th>
                                <th>Expires</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="token in tokens" :key="token.id">
                                <td style="vertical-align: middle;">
                                    <div class="font-weight-bold">{{ token.name }}</div>
                                    <small class="text-muted" v-if="token.created_at" style="font-size:12px;">
                                        Created {{ formatDate(token.created_at) }}
                                    </small>
                                </td>

                                <td style="vertical-align: middle;">
                                    <template v-if="token.scopes && token.scopes.length > 0">
                                        <span v-for="scope in token.scopes"
                                              :key="scope"
                                              class="badge mr-1 mb-1"
                                              :class="getScopeBadge(scope)">
                                            {{ scope === '*' ? 'all scopes' : scope }}
                                        </span>
                                    </template>
                                    <span v-else class="text-muted">&mdash;</span>
                                </td>

                                <td style="vertical-align: middle;">
                                    <span v-if="! token.expires_at" class="text-muted text-xs">
                                        Never
                                    </span>
                                    <span v-else
                                          :class="{ 'text-danger': isExpired(token) }"
                                          style="font-size:12px;"
                                          :title="formatDate(token.expires_at)">
                                        {{ formatDate(token.expires_at) }}
                                        <span v-if="isExpired(token)" class="font-weight-bold">(expired)</span>
                                    </span>
                                </td>

                                <td class="d-flex">
                                    <a class="btn btn-warning btn-sm" style="font-weight: bold;" @click="renew(token)">
                                        Renew
                                    </a>

                                    <span class="mx-1"></span>
                                    <a class="btn btn-danger btn-sm" style="font-weight: bold;" @click="revoke(token)">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-create-token" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Create Token
                        </h4>

                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-danger" v-if="form.errors.length > 0">
                            <p class="mb-0"><strong>Whoops!</strong> Something went wrong!</p>
                            <br>
                            <ul>
                                <li v-for="(error, index) in form.errors" :key="index">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <form role="form" @submit.prevent="store">
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label">Name</label>

                                <div class="col-md-6">
                                    <input id="create-token-name" type="text" class="form-control" name="name" v-model="form.name" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group row" v-if="scopes.length > 0">
                                <label class="col-md-4 col-form-label">Scopes</label>

                                <div class="col-md-6">
                                    <div v-for="scope in scopes" :key="scope.id">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    @click="toggleScope(scope.id)"
                                                    :checked="scopeIsAssigned(scope.id)">

                                                    {{ scope.id }}
                                            </label>
                                            <small class="text-muted d-block ml-4" v-if="scope.description">
                                                {{ scope.description }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>

                        <button type="button" class="btn btn-primary font-weight-bold" @click="store">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-access-token" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Personal Access Token
                        </h4>

                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Here is your new personal access token. This is the only time it will be shown so don't lose it!
                            You may now use this token to make API requests.
                        </p>

                        <textarea class="form-control" rows="10" readonly>{{ accessToken }}</textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                accessToken: null,

                tokens: [],
                scopes: [],

                form: {
                    name: '',
                    scopes: [],
                    errors: []
                }
            };
        },

        mounted() {
            this.prepareComponent();
        },

        methods: {

            prepareComponent() {
                this.getTokens();
                this.getScopes();

                $('#modal-create-token').on('shown.bs.modal', () => {
                    $('#create-token-name').focus();
                });
            },

            getScopeBadge(scope) {
                switch (scope) {
                    case '*':
                        return 'badge-danger';
                        break;

                    case 'read':
                        return 'badge-secondary';
                        break;

                    case 'push':
                        return 'badge-info';
                        break;

                    case 'write':
                    case 'follow':
                    case 'admin:read':
                    case 'admin:read:domain_blocks':
                    case 'admin:write':
                    case 'admin:write:domain_blocks':
                        return 'badge-danger';
                        break;

                    default:
                        break;
                }
            },


            getTokens() {
                axios.get('/oauth/personal-access-tokens')
                        .then(response => {
                            this.tokens = response.data;
                        });
            },

            getScopes() {
                axios.get('/oauth/scopes')
                        .then(response => {
                            this.scopes = response.data;
                        });
            },

            showCreateTokenForm() {
                $('#modal-create-token').modal('show');
            },

            store() {
                this.accessToken = null;

                this.form.errors = [];

                axios.post('/oauth/personal-access-tokens', this.form)
                        .then(response => {
                            this.form.name = '';
                            this.form.scopes = [];
                            this.form.errors = [];

                            this.tokens.push(response.data.token);

                            this.showAccessToken(response.data.accessToken);
                        })
                        .catch(error => {
                            if (typeof error.response.data === 'object') {
                                this.form.errors = _.flatten(_.toArray(error.response.data.errors));
                            } else {
                                this.form.errors = ['Something went wrong. Please try again.'];
                            }
                        });
            },

            toggleScope(scope) {
                if (this.scopeIsAssigned(scope)) {
                    this.form.scopes = _.reject(this.form.scopes, s => s === scope);
                } else {
                    this.form.scopes.push(scope);
                }
            },

            scopeIsAssigned(scope) {
                return _.indexOf(this.form.scopes, scope) >= 0;
            },

            showAccessToken(accessToken) {
                $('#modal-create-token').modal('hide');

                this.accessToken = accessToken;

                $('#modal-access-token').modal('show');
            },

            revoke(token) {
                swal({
                    title: 'Confirm token deletion',
                    text: 'Are you sure you want to delete this token? Any applications using it will stop working immediately.',
                    icon: "warning",
                    dangerMode: true,
                    buttons: true,
                })
                .then((val) => {
                    if (val) {
                        axios.delete('/oauth/personal-access-tokens/' + token.id)
                            .then(() => {
                                this.getTokens();
                            });
                    }
                });
            },

            renew(token) {
                swal({
                    title: 'Confirm token renewal',
                    text: 'Are you sure you want to renew this token? Any applications using it will stop working immediately, a new token will be generated and only shown once.',
                    icon: "warning",
                    dangerMode: true,
                    buttons: true
                })
                .then((val) => {
                    if (val) {
                        this.accessToken = null;

                        axios.post('/oauth/personal-access-tokens/' + token.id + '/renew')
                            .then(response => {
                                this.tokens = this.tokens
                                    .filter(t => t.id !== response.data.renewedTokenId);

                                this.tokens.push(response.data.token);

                                this.showAccessToken(response.data.accessToken);
                            })
                            .catch(error => {
                                alert('Unable to renew this token. Please try again.');
                            });
                    }
                });
            },

            formatDate(value) {
                if (! value) {
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
            },

            isExpired(token) {
                return !! token.expires_at && new Date(token.expires_at).getTime() < Date.now();
            }
        }
    }
</script>
