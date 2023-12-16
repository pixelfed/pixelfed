@extends('settings.template-vue')

@section('section')
<div>
    <div class="d-flex justify-content-between align-items-center">
        <div class="title d-flex align-items-center" style="gap: 1rem;">
            <p class="mb-0"><a href="/settings/privacy"><i class="far fa-chevron-left fa-lg"></i></a></p>
            <h3 class="font-weight-bold mb-0">Domain Blocks</h3>
        </div>
    </div>

    <p class="mt-3 mb-n2 small">You can block entire domains, this prevents users on that instance from interacting with your content and from you seeing content from that domain on public feeds.</p>

    <hr />

    <div v-if="!loaded" class="d-flex justify-content-center align-items-center flex-grow-1">
        <b-spinner />
    </div>

    <div v-else>
        <div class="mb-3 d-flex flex-column flex-md-row justify-content-between align-items-center" style="gap: 2rem;">
            <div style="width: 60%;">
                <div class="input-group align-items-center">
                    <input class="form-control form-control-sm rounded-lg" v-model="q" placeholder="Search by domain..." style="padding-right: 60px;" :disabled="!blocks || !blocks.length">
                    <div style="margin-left: -60px;width: 60px;z-index:3">
                        <button class="btn btn-link" type="button" style="font-size: 12px;text-decoration: none;" v-html="q && q.length ? 'Clear': '&nbsp;'" @click="searchAction()"></button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold px-3 flex-grow" @click="openModal">
                <i class="fas fa-plus mr-1"></i> New Block
            </button>
        </div>
        <div v-if="blocks && blocks.length" class="list-group">
            <div
                v-for="(item, idx) in chunks[index]"
                class="list-group-item">
                <div class="d-flex justify-content-between align-items-center font-weight-bold">
                <span>
                    <span v-text="item"></span>
                </span>
                <span class="btn-group">
                    <button type="button" class="btn btn-link btn-sm px-3 font-weight-bold" @click="handleUnblock(item)">Unblock</button>
                </span>
                </div>
            </div>
        </div>

        <nav v-if="blocks && blocks.length && chunks && chunks.length > 1" class="mt-3" aria-label="Domain block pagination">
            <ul class="pagination justify-content-center" style="gap: 1rem">
                <li
                    class="page-item"
                    :class="[ !index ? 'disabled' : 'font-weight-bold' ]"
                    :disabled="!index"
                    @click="paginate('prev')">
                    <span class="page-link px-5 rounded-lg">Previous</span>
                </li>
                <li
                    class="page-item"
                    :class="[ index + 1 === chunks.length ? 'disabled' : 'font-weight-bold' ]"
                    @click="paginate('next')">
                    <span class="page-link px-5 rounded-lg" href="#">Next</span>
                </li>
            </ul>
        </nav>

        <div v-if="!blocks || !blocks.length">
            <hr />
            <p class="lead text-center font-weight-bold">You are not blocking any domains.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    let app = new Vue({
        el: '#content',

        data: {
            loaded: false,
            q: undefined,
            blocks: [],
            filteredBlocks: [],
            chunks: [],
            index: 0,
            pagination: [],
        },

        watch: {
            q: function(newVal, oldVal) {
                this.filterResults(newVal)
            }
        },

        mounted() {
            this.fetchBlocks()
        },

        methods: {
            fetchBlocks() {
                axios.get('/api/v1/domain_blocks', { params: { 'limit': 200 }})
                .then(res => {
                    let pages = false
                    if(res.headers?.link) {
                        pages = this.parseLinkHeader(res.headers['link'])
                    }
                    this.blocks = res.data
                    if(!pages || !pages.hasOwnProperty('next')) {
                        this.buildList()
                    } else {
                        this.handlePagination(pages)
                    }
                })
                .catch(err => {
                    console.log(err.response)
                })
            },

            handlePagination(pages) {
                if(!pages || !pages.hasOwnProperty('next')) {
                    this.buildList()
                    return
                }
                this.pagination = pages
                this.fetchPagination()
            },

            buildList() {
                this.index = 0
                this.chunks = this.chunkify(this.blocks)
                this.loaded = true
            },

            buildSearchList() {
                this.index = 0
                this.chunks = this.chunkify(this.filteredBlocks)
                this.loaded = true
            },

            fetchPagination() {
                axios.get(this.pagination.next)
                .then(res => {
                    let pages = false
                    if(res.headers?.link) {
                        pages = this.parseLinkHeader(res.headers['link'])
                    }
                    this.blocks.push(...res.data)
                    if(!pages || !pages.hasOwnProperty('next')) {
                        this.buildList()
                    } else {
                        this.handlePagination(pages)
                    }
                })
                .catch(err => {
                    this.buildList()
                })
            },

            handleUnblock(domain) {
                this.loaded = false
                axios.delete('/api/v1/domain_blocks', {
                    params: {
                        domain: domain
                    }
                })
                .then(res => {
                    this.blocks = this.blocks.filter(d => d != domain)
                    this.buildList()
                })
                .catch(err => {
                    this.buildList()
                })
            },

            filterResults(query) {
                this.loaded = false
                let formattedQuery = query.trim().toLowerCase()
                this.filteredBlocks = this.blocks.filter(domain => domain.toLowerCase().startsWith(formattedQuery))
                this.buildSearchList()
            },

            searchAction($event) {
                event.currentTarget.blur()
                this.q = ''
            },

            openModal() {
                swal({
                    title: 'Domain Block',
                    text: 'Add domain to block, must start with https://',
                    content: "input",
                    button: {
                        text: "Block",
                        closeModal: false,
                    }
                }).then(val => {
                    if (!val) {
                        swal.stopLoading()
                        swal.close()
                        return
                    }

                    axios.post('/api/v1/domain_blocks', { domain: val })
                    .then(res => {
                        let parsedUrl = new URL(val)
                        swal.stopLoading()
                        swal.close()
                        this.index = 0
                        this.blocks.unshift(parsedUrl.hostname)
                        this.buildList()
                    })
                    .catch(err => {
                        swal.stopLoading()
                        swal.close()
                        if(err.response?.data?.message || err.response?.data?.error) {
                            swal('Error', err.response?.data?.message ?? err.response?.data?.error, 'error')
                        }
                    })
                })
            },

            chunkify(arr, len = 10) {
                var chunks = [],
                    i = 0,
                    n = arr.length

                while (i < n) {
                    chunks.push(arr.slice(i, i += len))
                }

                return chunks
            },

            paginate(dir) {
                if(dir === 'prev' && this.index > 0) {
                    this.index--
                    return
                }

                if(dir === 'next' && this.index + 1 < this.chunks.length) {
                    this.index++
                    return
                }
            },

            parseLinkHeader(linkHeader) {
                const links = {}

                if (!linkHeader) {
                    return links
                }

                linkHeader.split(',').forEach(part => {
                    const match = part.match(/<([^>]+)>;\s*rel="([^"]+)"/)
                    if (match) {
                        const url = match[1]
                        const rel = match[2]

                        if (rel === 'prev' || rel === 'next') {
                            links[rel] = url
                        }
                    }
                })

                return links
            }
        }
    })
</script>
@endpush
