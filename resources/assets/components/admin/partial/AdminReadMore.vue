<template>
    <div>
        <div class="mb-0" :style="{ 'font-size':`${fontSize}px` }">{{ contentText }}</div>
        <p class="mb-0"><a v-if="canStepExpand || (canExpand && !expanded)" class="font-weight-bold small" href="#" @click.prevent="expand()">Read more</a></p>
    </div>
</template>

<script>
    export default {
        props: {
            content: {
                type: String
            },
            maxLength: {
                type: Number,
                default: 140
            },
            fontSize: {
                type: String,
                default: "13"
            },
            step: {
                type: Boolean,
                default: false
            },
            stepLimit: {
                type: Number,
                default: 140
            },
            initialLimit: {
                type: Number,
                default: 10
            }
        },

        computed: {
            contentText: {
                get() {
                    if(this.step) {
                        const len = this.content.length;
                        const steps = len / this.stepLimit;
                        if(this.stepIndex == 1 || steps < this.stepIndex) {
                            this.canStepExpand = true;
                        }

                        return this.steppedTruncate();
                    }
                    if(this.content && this.content.length > this.maxLength) {
                        this.canExpand = true;
                    }
                    return this.expanded ? this.content : this.truncate();
                }
            }
        },

        data() {
            return {
                expanded: false,
                canExpand: false,
                canStepExpand: false,
                stepIndex: 1,
            }
        },

        methods: {
            expand() {
                if(this.step) {
                    this.stepIndex++;
                    this.canStepExpand = true;
                } else {
                    this.expanded = true;
                }
            },

            truncate() {
                if(!this.content || !this.content.length) {
                    return;
                }

                if(this.content && this.content.length < this.maxLength) {
                    return this.content;
                }

                return this.content.slice(0, this.maxLength) + '...';
            },

            steppedTruncate() {
                if(!this.content || !this.content.length) {
                    return;
                }
                const len = this.content.length;
                const steps = len / this.stepLimit;
                const maxLen = this.stepLimit * this.stepIndex;
                if(this.initialLimit != 10 && this.stepIndex === 1 && this.canStepExpand) {
                    this.canStepExpand = len > this.stepLimit;
                    return this.content.slice(0, this.initialLimit);
                } else if(this.canStepExpand && this.stepIndex < steps) {
                    return this.content.slice(0, maxLen);
                } else {
                    this.canStepExpand = false;
                    return this.content;
                }
            }
        }
    }
</script>
