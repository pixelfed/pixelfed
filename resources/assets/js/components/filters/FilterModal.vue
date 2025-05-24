<template>
    <div>
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show" data-backdrop="static" data-keyboard="false" tabindex="-1" style="display: block;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light d-flex align-items-center">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fal fa-filter text-dark mr-2"></i>
                            {{ isEditing ? $t("settings.filters.edit_filter") : $t("settings.filters.create_filter") }}
                        </h5>
                        <div class="ml-auto d-flex align-items-center">
                            <div class="custom-control custom-switch mr-3">
                                <input
                                type="checkbox"
                                class="custom-control-input"
                                id="wizard-toggle"
                                :checked="wizardMode"
                                @change="toggleWizardMode($event)"
                                >
                                <label class="custom-control-label" for="wizard-toggle">
                                    <small>{{ !wizardMode ? $t("settings.filters.advance_mode") : $t("settings.filters.simple_mode") }}</small>
                                </label>
                            </div>
                            <button type="button" class="close" @click="closeModal()">
                                <span class="text-muted"><i class="fal fa-times"></i></span>
                            </button>
                        </div>
                    </div>

                    <form v-if="!wizardMode" @submit.prevent="saveFilter" class="simple-wizard">
                        <div class="modal-body px-4">
                            <div class="form-group">
                                <label for="title" class="label">{{ $t('settings.filters.filter_title') }}</label>
                                <input
                                    v-model="formData.title"
                                    type="text"
                                    id="title"
                                    class="form-control form-control-lg form-control-mat"
                                    :placeholder="$t('settings.filters.enter_filter_title')"
                                    required
                                />
                            </div>

                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <label class="label">{{ $t("settings.filters.keywords") }}</label>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center" style="gap: 1rem;">
                                        <p class="small text-muted mb-0">{{ $t("settings.filters.legend") }}</p>
                                        <button
                                            type="button"
                                            class="btn btn-xs rounded-pill keyword-tag keyword-tag-whole py-1 px-3"
                                            @click="showWholeWordExplanation()"
                                            >
                                            <i class="far fa-info-circle mr-1"></i>
                                            {{ $t("settings.filters.whole_word") }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-xs rounded-pill keyword-tag keyword-tag-partial py-1 px-3"
                                            @click="showPartialPhraseExplanation()"
                                            >
                                            <i class="far fa-info-circle mr-1"></i>
                                            {{ $t("settings.filters.partial_word") }}
                                        </button>
                                    </div>
                                </div>
                                <div class="keyword-tags p-2">
                                    <div class="d-flex flex-wrap">
                                        <div
                                            v-for="(keyword, index) in formData.keywords"
                                            :key="index"
                                            class="keyword-tag rounded-pill px-3 py-1 mr-2 mb-2 d-flex align-items-center"
                                            :class="{'keyword-tag-whole': keyword.whole_word, 'keyword-tag-partial': !keyword.whole_word}"
                                            >
                                            <div
                                                class="cursor-pointer"
                                                @click="toggleWholeWord(index)"
                                                >
                                                {{ keyword.keyword }}
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm p-0 ml-2"
                                                :class="{'keyword-tag-whole-times': keyword.whole_word, 'keyword-tag-partial-times': !keyword.whole_word}"
                                                @click="removeKeyword(keyword)"
                                                >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <input
                                            v-if="canAddMoreKeywordsWithoutDuplicate"
                                            v-model="newKeyword"
                                            type="text"
                                            :maxlength="40"
                                            class="form-control border-0 bg-transparent rounded-pill flex-grow-1 mb-2"
                                            :placeholder=" $t('settings.filters.add_keyword') "
                                            @keydown.enter.prevent="addKeywordFromInput"
                                            style="min-width: 150px;"
                                            />
                                    </div>
                                </div>
                                <div v-if="isDuplicateError" class="alert alert-warning rounded-lg mt-2 p-2 small">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    {{ $t("settings.filters.duplicate_not_allowed") }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label">{{ $t("settings.filters.filter_action") }}</label>
                                <div class="filter-action-options">
                                    <div class="custom-control custom-radio mb-2">
                                        <input
                                        type="radio"
                                        id="action-blur"
                                        name="filter_action"
                                        class="custom-control-input"
                                        value="blur"
                                        v-model="formData.filter_action"
                                        />
                                        <label class="custom-control-label d-flex align-items-center" for="action-blur">
                                            <span class="badge badge-primary mr-2">Blur</span>
                                            {{ $t("settings.filters.hide_media_blur") }}
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio mb-2">
                                        <input
                                        type="radio"
                                        id="action-warn"
                                        name="filter_action"
                                        class="custom-control-input"
                                        value="warn"
                                        v-model="formData.filter_action"
                                        />
                                        <label class="custom-control-label d-flex align-items-center" for="action-warn">
                                            <span class="badge badge-warning mr-2">Warning</span>
                                            {{ $t("settings.filters.show_warning") }}
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio mb-2">
                                        <input
                                        type="radio"
                                        id="action-hide"
                                        name="filter_action"
                                        class="custom-control-input"
                                        value="hide"
                                        v-model="formData.filter_action"
                                        />
                                        <label class="custom-control-label d-flex align-items-center" for="action-hide">
                                            <span class="badge badge-danger mr-2">Hidden</span>
                                            {{ $t("settings.filters.hide_content_completely") }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label">{{ $t("settings.filters.apply_filters_to") }}</label>
                                <div class="row">
                                    <div v-if="contextItemKeys.includes('home')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-home"
                                            value="home"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-home">
                                                {{ $t("settings.filters.home_timeline") }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="contextItemKeys.includes('notifications')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-notifications"
                                            value="notifications"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-notifications">
                                                {{ $t("settings.filters.notifications") }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="contextItemKeys.includes('public')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-public"
                                            value="public"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-public">
                                                {{ $t("settings.filters.public_timeline") }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="contextItemKeys.includes('tags')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-hashtags"
                                            value="tags"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-hashtags">
                                                {{ $t("settings.filters.hashtags") }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="contextItemKeys.includes('thread')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-thread"
                                            value="thread"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-thread">
                                                {{ $t("settings.filters.conversations") }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="contextItemKeys.includes('groups')" class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="context-groups"
                                            value="groups"
                                            v-model="formData.context"
                                            />
                                            <label class="custom-control-label" for="context-groups">
                                                {{ $t("settings.filters.groups") }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="duration" class="label"> {{ $t("settings.filters.duration") }}</label>
                                <select v-model="selectedDuration" id="duration" class="custom-select custom-select-lg form-control-mat">
                                    <option value="0">{{ $t("settings.filters.forever") }}</option>
                                    <option value="1800">{{ $t("settings.filters.30_minutes") }}</option>
                                    <option value="3600">{{ $t("settings.filters.1_hour") }}</option>
                                    <option value="21600">{{ $t("settings.filters.6_hours") }}</option>
                                    <option value="43200">{{ $t("settings.filters.12_hours") }}</option>
                                    <option value="86400">{{ $t("settings.filters.1_day") }}</option>
                                    <option value="604800">{{ $t("settings.filters.1_week") }}</option>
                                    <option value="-1">{{ $t("settings.filters.1_week") }}</option>
                                </select>
                                <div v-if="selectedDuration === '-1'" class="input-group mt-2">
                                    <input
                                    v-model="customDuration"
                                    type="number"
                                    min="1"
                                    class="form-control form-control-lg form-control-mat"
                                    :placeholder="$t('settings.filters.enter_duration_in_seconds')"
                                    />
                                    <div class="input-group-append overflow-hidden">
                                        <span class="input-group-text">seconds</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" @click="closeModal()" class="btn btn-outline-secondary font-weight-light rounded-pill">
                                   {{ $t('common.cancel')}}
                                </button>

                                <button
                                    v-if="isEditing"
                                    type="button"
                                    class="btn btn-outline-danger font-weight-light rounded-pill"
                                    @click="deleteFilter()">
                                    {{ $t('common.delete')}}
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary font-weight-bold rounded-pill" :disabled="!isValid">
                                <template v-if="isPosting">
                                    <div class="spinner-border text-white mx-4 spinner-border-sm" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </template>
                                <template v-else>
                                    {{ isEditing ? $t("settings.filters.save_changes") :  $t("settings.filters.create_filter") }}
                                </template>
                            </button>
                        </div>
                    </form>

                    <form v-else>
                        <div class="modal-body p-0">
                            <div class="wizard-progress bg-light py-2 px-md-5 d-flex justify-content-between">
                                <div
                                    v-for="(step, index) in wizardSteps"
                                    :key="index"
                                    class="wizard-step d-flex flex-column align-items-center px-md-2 position-relative"
                                    :class="{'active': currentStep === index, 'completed': currentStep > index}"
                                    @click="goToStep(index)"
                                    >
                                    <div class="wizard-step-indicator rounded-circle d-flex align-items-center justify-content-center mb-1">
                                        <span v-if="currentStep > index"><i class="fas fa-check"></i></span>
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>
                                    <span
                                        class="wizard-step-label small"
                                        :class="[ currentStep === index ? 'text-dark font-weight-bold' : 'text-lighter text-weight-light']">{{ step.label }}
                                    </span>
                                </div>
                            </div>

                            <div class="wizard-content py-4 px-3 px-md-5">
                                <div v-if="currentStep === 0" key="step1" class="step-content">
                                    <div class="step-content-info text-center mb-4">
                                        <div class="step-content-info-icon">
                                            <i class="fal fa-filter fa-3x"></i>
                                        </div>
                                        <h4>{{ $t('settings.filters.name_your_filter') }}</h4>
                                        <p class="text-muted">{{ $t('settings.filters.give_your_filter_a_name') }}</p>
                                    </div>
                                    <div class="form-group">
                                        <label for="wizard-title">{{ $t('settings.filters.filter_title') }}</label>
                                        <input
                                        v-model="formData.title"
                                        type="text"
                                        id="wizard-title"
                                        class="form-control form-control-lg"
                                        :placeholder="$t('settings.filters.my_filter_name')"
                                        required
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label for="wizard-duration">{{ $t('settings.filters.filter_duration') }}</label>
                                        <select v-model="selectedDuration" id="wizard-duration" class="custom-select">
                                            <option value="0">{{ $t("settings.filters.forever") }}</option>
                                            <option value="1800">{{ $t("settings.filters.30_minutes") }}</option>
                                            <option value="3600">{{ $t("settings.filters.1_hour") }}</option>
                                            <option value="21600">{{ $t("settings.filters.6_hours") }}</option>
                                            <option value="43200">{{ $t("settings.filters.12_hours") }}</option>
                                            <option value="86400">{{ $t("settings.filters.1_day") }}</option>
                                            <option value="604800">{{ $t("settings.filters.1_week") }}</option>
                                            <option value="-1">{{ $t("settings.filters.1_week") }}</option>
                                        </select>
                                        <div v-if="selectedDuration === '-1'" class="input-group mt-2">
                                            <input
                                            v-model="customDuration"
                                            type="number"
                                            min="1"
                                            max="63072000"
                                            class="form-control"
                                            :placeholder="$t('settings.filters.enter_duration_in_seconds')"
                                            />
                                            <div class="input-group-append">
                                                <span class="input-group-text">seconds</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="currentStep === 1" key="step2" class="step-content">
                                    <div class="step-content-info text-center mb-4">
                                        <div class="step-content-info-icon">
                                            <i class="fal fa-key fa-3x"></i>
                                        </div>
                                        <h4>{{ $t('settings.filters.add_filter_keywords')}}</h4>
                                        <p class="text-muted"  v-html="$t('settings.filters.add_word_or_phrase')"></p>
                                    </div>

                                    <div class="keywords-container d-flex flex-column align-items-center">
                                        <div v-for="(keyword, index) in formData.keywords" :key="index" class="keyword-item mb-4 position-relative w-75">
                                            <div class="input-group">
                                                <input
                                                v-model="keyword.keyword"
                                                type="text"
                                                class="form-control form-control-lg border-right-0"
                                                :class="{
                                                    'border-primary': keyword.whole_word && !keywordErrors[index],
                                                    'border-info': !keyword.whole_word && !keywordErrors[index],
                                                    'is-invalid': keywordErrors[index]
                                                }"
                                                placeholder="Enter keyword or phrase"
                                                maxlength="40"
                                                @input="checkDuplicateKeyword(index)"
                                                />

                                                <div class="input-group-append">
                                                    <button
                                                    type="button"
                                                    class="btn btn-outline-secondary border-left-0 bg-white"
                                                    :class="{'text-primary': keyword.whole_word, 'text-info': !keyword.whole_word}"
                                                    @click="toggleWholeWord(index)"
                                                    >
                                                    <i class="fas" :class="{'fa-font': keyword.whole_word, 'fa-text-width': !keyword.whole_word}"></i>
                                                </button>
                                                <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                @click="removeKeyword(keyword)"
                                                >
                                                <i class="fas fa-trash"></i>
                                                </button>
                                                </div>
                                            </div>

                                            <div v-if="keywordErrors[index]" class="text-danger small mt-1">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ keywordErrors[index] }}
                                            </div>

                                            <small class="text-muted">
                                                {{ keyword.whole_word ? $t('settings.filters.whole_word_match') :  $t('settings.filters.partial_word_match')}}
                                            </small>
                                        </div>

                                        <button
                                            v-if="canAddMoreKeywords"
                                            type="button"
                                            class="btn btn-outline-primary mt-3 font-weight-light rounded-pill"
                                            @click="addKeyword"
                                        >
                                            <i class="fas fa-plus mr-1"></i> {{ $t('settings.filters.add_another_keyword')}}
                                        </button>

                                        <div v-if="isDuplicateError" class="alert alert-warning mt-4 w-75">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            {{ $t('settings.filters.please_remove_duplicate_keywords')}}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="currentStep === 2" key="step3" class="step-content">
                                    <div class="step-content-info text-center mb-4">
                                        <div class="step-content-info-icon">
                                            <i class="fal fa-shield-alt fa-3x"></i>
                                        </div>
                                        <h4>{{ $t('settings.filters.choose_filter_action')}}</h4>
                                        <p class="text-muted">{{ $t('settings.filters.choose_filter_action_description')}}</p>
                                    </div>

                                    <div class="card-deck">
                                        <div
                                            class="card shadow-none text-center p-3 filter-action-card"
                                            :class="{'selected': formData.filter_action === 'blur'}"
                                            @click="formData.filter_action = 'blur'"
                                            >
                                            <div class="card-body">
                                                <i class="fas fa-tint fa-2x text-info mb-3"></i>
                                                <h5 class="card-title">Blur</h5>
                                                <p class="card-text text-muted small">{{ $t('settings.filters.hide_media_blur') }}</p>
                                            </div>
                                        </div>
                                        <div
                                            class="card shadow-none text-center p-3 filter-action-card"
                                            :class="{'selected': formData.filter_action === 'warn'}"
                                            @click="formData.filter_action = 'warn'"
                                        >
                                            <div class="card-body">
                                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                                <h5 class="card-title">Warn</h5>
                                                <p class="card-text text-muted small">{{ $t('settings.filters.show_warning') }}</p>
                                            </div>
                                        </div>
                                        <div
                                            class="card shadow-none text-center p-3 filter-action-card"
                                            :class="{'selected': formData.filter_action === 'hide'}"
                                            @click="formData.filter_action = 'hide'"
                                        >
                                            <div class="card-body">
                                                <i class="fas fa-eye-slash fa-2x text-danger mb-3"></i>
                                                <h5 class="card-title">Hide</h5>
                                                <p class="card-text text-muted small">{{ $t('settings.filters.hide_completely') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="currentStep === 3" key="step4" class="step-content">
                                    <div class="step-content-info text-center mb-4">
                                        <div class="step-content-info-icon">
                                            <i class="fal fa-map fa-3x"></i>
                                        </div>
                                        <h4>{{ $t('settings.filters.choose_where_to_apply') }}</h4>
                                        <p class="text-muted">{{ $t('settings.filters.choose_where_to_apply_description') }}</p>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3" v-for="item in contextItems" :key="item.value">
                                            <div
                                                class="card shadow-none rounded-lg context-card p-3 h-100"
                                                :class="{'selected': formData.context.includes(item.value)}"
                                                @click="toggleContext(item.value)"
                                            >
                                                <div class="card-body d-flex align-items-center">
                                                    <div class="custom-control custom-checkbox mr-2">
                                                        <input
                                                            class="custom-control-input"
                                                            type="checkbox"
                                                            :id="`wizard-context-${item.value}`"
                                                            :value="item.value"
                                                            v-model="formData.context"
                                                        />
                                                        <label class="custom-control-label" :for="`wizard-context-${item.value}`"></label>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1">{{ item.label }}</h5>
                                                        <p class="text-muted mb-0 small">{{ item.description }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="currentStep === 4" key="step5" class="step-content">
                                    <div class="step-content-info text-center mb-4">
                                        <div class="step-content-info-icon bg-success border-success">
                                            <i class="fas fa-check fa-3x text-white"></i>
                                        </div>
                                        <h4>{{ $t('settings.filters.review_your_filter') }}</h4>
                                        <p class="text-muted">{{ $t('settings.filters.review_your_filter_description') }}</p>
                                    </div>
                                    <div class="card shadow-none border rounded-lg mb-3">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0 text-center font-weight-light">{{ formData.title || 'Untitled Filter' }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4 font-weight-bold">{{ $t('settings.filters.keywords')  }}:</div>
                                                <div class="col-md-8">
                                                    <div v-if="formData.keywords.length > 0">
                                                        <span
                                                            v-for="(keyword, idx) in formData.keywords.filter(k => k.keyword)"
                                                            :key="idx"
                                                            class="badge badge-pill badge-light badge-lg border mr-1 mb-1 p-2"
                                                        >
                                                            {{ keyword.keyword }}
                                                            <span v-if="keyword.whole_word" class="small font-italic ml-1">(whole)</span>
                                                        </span>
                                                    </div>
                                                    <span v-else class="text-muted">{{ $t('settings.filters.no_keywords_specified')  }}</span>
                                                </div>
                                            </div>
                                            <div class="row mb-4">
                                                <div class="col-md-4 font-weight-bold">{{ $t('settings.filters.action')  }}:</div>
                                                <div class="col-md-8">
                                                    <span
                                                        class="font-weight-bold mb-1"
                                                    >
                                                        <div v-html="renderActionDescription()"></div>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 font-weight-bold">Applied to:</div>
                                                <div class="col-md-8">
                                                    <span
                                                        v-for="context in formData.context"
                                                        :key="context"
                                                        class="badge badge-pill badge-light border mr-1 mb-1 p-2"
                                                        >
                                                        {{ formatContext(context) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 font-weight-bold">{{ $t("settings.filters.duration") }}:</div>
                                                <div class="col-md-8 text-muted small">
                                                    <span v-if="selectedDuration === '0'">{{ $t("settings.filters.forever") }}</span>
                                                    <span v-else-if="selectedDuration === '1800'">{{ $t("settings.filters.30_minutes") }}</span>
                                                    <span v-else-if="selectedDuration === '3600'">{{ $t("settings.filters.1_hour") }}</span>
                                                    <span v-else-if="selectedDuration === '21600'">{{ $t("settings.filters.6_hours") }}</span>
                                                    <span v-else-if="selectedDuration === '43200'">{{ $t("settings.filters.12_hours") }}</span>
                                                    <span v-else-if="selectedDuration === '86400'">{{ $t("settings.filters.1_day") }}</span>
                                                    <span v-else-if="selectedDuration === '604800'">{{ $t("settings.filters.1_week") }}</span>
                                                    <span v-else-if="selectedDuration === '-1'">{{ customDuration }} seconds</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light justify-content-between">
                            <div>
                                <button
                                        type="button"
                                        class="btn btn-outline-secondary font-weight-light rounded-pill"
                                        @click="currentStep > 0 ? currentStep-- : closeModal()"
                                    >
                                    {{ currentStep > 0 ? 'Back' : 'Cancel' }}
                                </button>

                                <button
                                    v-if="isEditing"
                                    type="button"
                                    class="btn btn-outline-danger font-weight-light rounded-pill"
                                    @click="deleteFilter()"
                                >
                                    {{ $t('common.delete')}}
                                </button>
                            </div>
                            <div>
                                <button
                                    v-if="currentStep < wizardSteps.length - 1"
                                    type="button"
                                    class="btn btn-primary font-weight-bold rounded-pill"
                                    @click="nextStep"
                                    :disabled="!canContinue"
                                >
                                    {{ $t('common.continue')}}
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="saveFilter"
                                    class="btn btn-success font-weight-bold rounded-pill"
                                    :disabled="!isValid || isPosting"
                                >
                                    <template v-if="isPosting">
                                        <div class="spinner-border text-white mx-4 spinner-border-sm" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        {{ isEditing ? $t("settings.filters.save_changes") : $t("settings.filters.create_filter")  }}
                                    </template>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'FilterModal',
        props: {
            filter: {
                type: Object,
                default: null
            },
            isEditing: {
                type: Boolean,
                default: false
            },
            wizardMode: {
                type: Boolean,
                default: true
            }
        },
        data() {
            return {
                currentStep: 0,
                formData: {
                    title: '',
                    keywords: [],
                    keywords_attributes: [],
                    context: [],
                    irreversible: false,
                    filter_action: 'warn',
                    expires_in: 0
                },
                newKeyword: '',
                selectedDuration: '0',
                customDuration: null,
                keywordErrors: {},
                isDuplicateError: false,
                isPosting: false,
                contextItems: [
                    {
                        value: 'home',
                        label: 'Home timeline',
                        description: 'Filter content on your main feed'
                    },
                    // {
                    //   value: 'notifications',
                    //   label: 'Notifications',
                    //   description: 'Filter content in your notifications'
                    // },
                    {
                        value: 'public',
                        label: 'Public timelines',
                        description: 'Filter content on public and explore pages'
                    },
                    // {
                    //   value: 'thread',
                    //   label: 'Conversations',
                    //   description: 'Filter content in threads and replies'
                    // },
                    {
                        value: 'tags',
                        label: 'Hashtags',
                        description: 'Filter content in hashtag feeds'
                    },
                    // {
                    //   value: 'groups',
                    //   label: 'Groups',
                    //   description: 'Filter content in groups and group feeds'
                    // },
                ],
                wizardSteps: [
                    { label: this.$t('settings.filters.titleAdvance'), field: 'title' },
                    { label: this.$t('settings.filters.keywords'), field: 'keywords' },
                    { label: this.$t('settings.filters.action'), field: 'filter_action' },
                    { label: this.$t('settings.filters.context'), field: 'context' },
                    { label: this.$t('settings.filters.review'), field: null }
                ]
            }
        },

        watch: {
            newKeyword: {
                deep: true,
                handler: function(old) {
                    this.validateKeywords()
                }
            }
        },

        computed: {
            contextItemKeys() {
                return this.contextItems.map(c => c.value);
            },
            isValid() {
                const hasDuplicates = this.isDuplicateError;

                return !hasDuplicates &&
                this.formData.title &&
                this.formData.context.length > 0 &&
                (this.formData.keywords.length === 0 ||
                    this.formData.keywords.some(k => k.keyword && k.keyword.trim() !== ''));
            },
            canAddMoreKeywords() {
                return (this.formData.keywords.length === 0 ||
                    this.formData.keywords.length < 10) && !this.isDuplicateError
            },
            canAddMoreKeywordsWithoutDuplicate() {
                return (this.formData.keywords.length === 0 ||
                    this.formData.keywords.length < 10)
            },
            canContinue() {
                switch(this.currentStep) {
                case 0:
                    return this.formData.title && this.formData.title.trim() !== '';
                case 1:
                    return !this.isDuplicateError && this.formData.keywords.filter(k => k.keyword.trim() !== '').length;
                case 3:
                    return this.formData.context.length > 0;
                default:
                    return true;
                }
            }
        },
        mounted() {
            document.body.classList.add('modal-open');
            if (this.filter) {
                this.formData = {
                    id: this.filter.id,
                    title: this.filter.title || '',
                    keywords: this.filter.keywords ? [...this.filter.keywords] : [],
                    keywords_attributes: this.filter.keywords ? [...this.filter.keywords] : [],
                    context: Array.isArray(this.filter.context) ? [...this.filter.context] : [],
                    irreversible: this.filter.irreversible || false,
                    filter_action: this.filter.filter_action || 'warn',
                    expires_in: 0
                };

                if (this.formData.keywords.length === 0) {
                    this.addKeyword();
                }

                if (this.filter.expires_at) {
                    const now = new Date();
                    const expiresAt = new Date(this.filter.expires_at);
                    const secondsRemaining = Math.floor((expiresAt - now) / 1000);
                    const standardDurations = [1800, 3600, 21600, 43200, 86400, 604800];
                    const matchedDuration = standardDurations.find(d => Math.abs(d - secondsRemaining) < 60);
                    if (matchedDuration) {
                        this.selectedDuration = String(matchedDuration);
                    } else {
                        this.selectedDuration = '-1';
                        this.customDuration = secondsRemaining;
                    }
                }
            } else {
                this.addKeyword();
            }
        },
        beforeDestroy() {
            this.isPosting = false;
            document.body.classList.remove('modal-open');
        },
        methods: {
            addKeyword() {
                this.formData.keywords.push({
                    keyword: '',
                    whole_word: true
                });
                this.formData.keywords_attributes.push({
                    keyword: '',
                    whole_word: true
                });

                this.$set(this.keywordErrors, this.formData.keywords.length - 1, '');
            },
            addKeywordFromInput() {
                if (!this.newKeyword || this.newKeyword.trim() === '') return;

                const trimmedKeyword = this.newKeyword.trim();

                const isDuplicate = this.formData.keywords.some(k =>
                    k.keyword.toLowerCase() === trimmedKeyword.toLowerCase()
                    );

                if (isDuplicate) {
                    this.isDuplicateError = true;
                    return;
                }

                if(!this.canAddMoreKeywords) {
                    return;
                }

                this.formData.keywords.push({
                    keyword: trimmedKeyword,
                    whole_word: true
                });

                this.formData.keywords_attributes.push({
                    keyword: trimmedKeyword,
                    whole_word: true
                });

                this.newKeyword = '';
                this.isDuplicateError = false;
            },

            validateKeywords() {
                const keywordSet = new Set();
                let hasErrors = false;

                this.keywordErrors = {};
                this.isDuplicateError = false;

                this.formData.keywords.forEach((keywordObj, index) => {
                    if (!keywordObj.keyword || keywordObj.keyword.trim() === '') {
                        this.$set(this.keywordErrors, index, '');
                        return;
                    }

                    const normalizedKeyword = keywordObj.keyword.toLowerCase().trim();

                    if (keywordSet.has(normalizedKeyword)) {
                        this.$set(this.keywordErrors, index, 'Duplicate keyword');
                        hasErrors = true;
                        this.isDuplicateError = true;
                    } else {
                        keywordSet.add(normalizedKeyword);
                        this.$set(this.keywordErrors, index, '');
                    }
                });

                return !hasErrors;
            },

            toggleWizardMode(event) {
                if(this.wizardMode) {
                    this.formData.keywords = this.formData.keywords.filter(k => k.keyword && k.keyword.trim() !== '');
                    this.formData.keywords_attributes = this.formData.keywords.filter(k => k.keyword && k.keyword.trim() !== '');
                } else {
                    if(!this.formData.keywords.length) {
                        this.formData.keywords.push({
                            keyword: '',
                            whole_word: true
                        });
                    }
                }
                this.$emit('toggle', event.target.checked);
            },

            saveFilter() {
                if (!this.validateKeywords() || !this.isValid || this.isPosting) {
                    return;
                }

                this.isPosting = true;
                if(!this.isEditing) {
                    this.formData.keywords_attributes = this.formData.keywords.filter(k => k.keyword && k.keyword.trim() !== '');
                }

                if (this.selectedDuration === '-1' && this.customDuration) {
                    this.formData.expires_in = parseInt(this.customDuration);
                } else {
                    this.formData.expires_in = parseInt(this.selectedDuration);
                }
                setTimeout(() => {
                    this.$emit('save', this.formData);
                    this.isPosting = false;
                }, 1500)
            },

            checkDuplicateKeyword(index) {
                const currentKeyword = this.formData.keywords[index].keyword.toLowerCase().trim();

                if (!currentKeyword) {
                    this.$set(this.keywordErrors, index, '');
                    return true;
                }

                const isDuplicate = this.formData.keywords.some((k, i) =>
                    i !== index &&
                    k.keyword &&
                    k.keyword.toLowerCase().trim() === currentKeyword
                    );

                if (isDuplicate) {
                    this.$set(this.keywordErrors, index, 'Duplicate keyword');
                    this.isDuplicateError = true;
                    return false;
                } else {
                    this.$set(this.keywordErrors, index, '');
                    this.isDuplicateError = Object.values(this.keywordErrors).some(error => error !== '');
                    return true;
                }
            },

            close() {
                this.closeModal();
            },

            closeModal() {
                document.body.classList.remove('modal-open');
                this.$emit('close');
            },

            deleteFilter() {
                this.$emit('delete');
            },

            removeKeyword(keywordObj) {
                const attrIndex = this.formData.keywords_attributes.findIndex(item =>
                    item.keyword === keywordObj.keyword &&
                    (item.id === keywordObj.id || (!item.id && !keywordObj.id))
                );

                if (attrIndex !== -1) {
                    this.formData.keywords_attributes[attrIndex]['_destroy'] = true;
                }

                const keywordIndex = this.formData.keywords.findIndex(item =>
                    item.keyword === keywordObj.keyword &&
                    (item.id === keywordObj.id || (!item.id && !keywordObj.id))
                );

                if (keywordIndex !== -1) {
                    this.formData.keywords.splice(keywordIndex, 1);
                }

                if (this.formData.keywords.length === 0 && this.wizardMode) {
                    this.addKeyword();
                }

                this.validateKeywords();
            },

            toggleContext(contextValue) {
                const index = this.formData.context.indexOf(contextValue);
                if (index === -1) {
                    this.formData.context.push(contextValue);
                } else {
                    this.formData.context.splice(index, 1);
                }
            },

            formatContext(context) {
                const contexts = {
                    'home': 'Home feed',
                    'notifications': 'Notifications',
                    'public': 'Public feeds',
                    'thread': 'Conversations',
                    'tags': 'Hashtags',
                    'groups': 'Groups'
                };
                return contexts[context] || context;
            },

            nextStep() {
                this.validateKeywords();
                if (this.currentStep < this.wizardSteps.length - 1 && this.canContinue) {
                    this.currentStep++;
                }
            },

            goToStep(stepIndex) {
                if (this.currentStep === 1) {
                    this.validateKeywords();
                }
                if (stepIndex <= this.currentStep) {
                    this.currentStep = stepIndex;
                }
            },

            toggleWholeWord(index) {
                this.formData.keywords[index].whole_word = !this.formData.keywords[index].whole_word;
            },

            renderActionDescription() {
                if(this.formData.filter_action === 'warn') {
                    return `<div><i class="fas fa-exclamation-triangle text-warning mr-1"></i> <strong>Warn</strong></div>`
                }
                if(this.formData.filter_action === 'blur') {
                    return `<div><i class="fas fa-tint mr-1 text-info"></i> <strong>Blur</strong></div>`
                }
                if(this.formData.filter_action === 'hide') {
                    return `<div><i class="fas fa-eye-slash mr-1 text-danger"></i> <strong>Hide</strong></div>`
                }
            },

            showWholeWordExplanation() {
                let content = document.createElement('div');
                content.classList = 'p-4';
                content.style.textAlign = 'left';
                content.style.marginTop = '20px';

                let title = document.createElement('h4');
                title.textContent = 'Whole Word Matching';
                title.style.fontWeight = 'bold';
                title.style.marginBottom = '15px';
                title.style.paddingBottom = '15px';
                title.style.borderBottom = '1px solid #ccc';

                let description = document.createElement('p');
                description.textContent = 'When enabled, keywords will only match complete words.';
                description.style.marginBottom = '15px';

                let example = document.createElement('p');
                example.textContent = 'Example: If your keyword is "cat", it will match "I have a cat" but won\'t match "category" or "concatenate".';
                example.style.marginBottom = '15px';

                let usage = document.createElement('p');
                usage.textContent = 'This is useful when you want to filter specific terms without affecting words that contain those letters as part of a larger word.';

                content.appendChild(title);
                content.appendChild(description);
                content.appendChild(example);
                content.appendChild(usage);

                swal({
                    title: '',
                    text: '',
                    html: true,
                    customClass: 'word-matching-modal',
                    content: content,
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#6c7cff'
                });
            },

            showPartialPhraseExplanation() {
                var content = document.createElement('div');
                content.classList = 'p-4';
                content.style.textAlign = 'left';
                content.style.marginTop = '20px';

                var title = document.createElement('h4');
                title.textContent = 'Partial Phrase Matching';
                title.style.fontWeight = 'bold';
                title.style.marginBottom = '15px';
                title.style.paddingBottom = '15px';
                title.style.borderBottom = '1px solid #ccc';

                var description = document.createElement('p');
                description.textContent = 'When enabled, keywords will match any text containing these characters.';
                description.style.marginBottom = '15px';

                var example = document.createElement('p');
                example.textContent = 'Example: If your keyword is "cat", it will match "I have a cat" as well as "category" and "concatenate".';
                example.style.marginBottom = '15px';

                var usage = document.createElement('p');
                usage.textContent = 'This is useful when you want to filter variations of words or when the same letters might appear in different contexts.';

                content.appendChild(title);
                content.appendChild(description);
                content.appendChild(example);
                content.appendChild(usage);

                swal({
                    title: '',
                    text: '',
                    html: true,
                    customClass: 'word-matching-modal',
                    content: content,
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#6c7cff'
                });
            }
        }
    }
</script>

<style scoped>
.custom-control-label {
    cursor: pointer;
}

.modal-content {
    border-radius: 0.5rem;
}

.modal-header, .modal-footer {
    border-color: rgba(0, 0, 0, 0.05);
}

.wizard-progress {
    position: relative;
    display: flex;
    justify-content: space-between;
    padding: 1rem 3rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.wizard-progress:after {
    content: '';
    position: absolute;
    top: 26px;
    left: 15%;
    width: 70%;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;

    @media(min-width: 991px) {
        left: 10%;
        width: 80%;
    }
}

.wizard-step {
    z-index: 2;
    cursor: pointer;
    opacity: 1;
    transition: all 0.2s ease;
}

.simple-wizard label {
    font-weight: 200;
}

.simple-wizard .label {
    width: 100%;
    color: var(--muted);
    font-weight: 200;
    margin-top: 1rem;
    font-size: 18px;
}

.wizard-step.active {
    opacity: 1;
    transform: scale(1.05);
}

.wizard-step.completed {
    opacity: 1;
}

.wizard-step-indicator {
    width: 36px;
    height: 36px;
    background-color: #e9ecef;
    color: #6c757d;
    font-weight: bold;
    transition: all 0.2s ease;
}

.wizard-step.active .wizard-step-indicator {
    background-color: #007bff;
    color: white;
}

.wizard-step.completed .wizard-step-indicator {
    background-color: #28a745;
    color: white;
}

.wizard-step-label {
    white-space: nowrap;
    font-weight: 500;
}

.wizard-content {
    max-height: 50dvh;

    @media(min-width: 991px) {
        min-height: 70dvh;
    }
}

.step-content {
    animation: fadeIn 0.5s;
}

.step-content-info {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    margin: 1.5rem auto 2rem auto;
    padding-bottom: 3rem;
}

.step-content-info-icon {
    display: none;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 2rem;
    border: 1px solid #bbb;
    border-radius: 100%;
    color: #bbb;
    margin-bottom: 2rem;


    @media(min-width: 991px) {
        display: flex;
    }
}

.step-content-info-icon i {
    color: #bbb;
}

.filter-action-card, .context-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
}

.filter-action-card:hover, .context-card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
    border-color: #c8d1d9;
}

.filter-action-card.selected, .context-card.selected {
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.keyword-item {
    transition: all 0.3s ease;
}

.keyword-item:hover {
    transform: translateY(-2px);
}

.is-invalid {
    border-color: #dc3545 !important;
    padding-right: calc(1.5em + 0.75rem) !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
}

.invalid-feedback, .text-danger {
    display: block;
    animation: fadeIn 0.3s;
}

.alert {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.keyword-tags {
    border: 1px solid #E5E5E5;
    border-radius: 30px;
    min-height: 100px;
    background-color: #F7F7FA;
}

.form-control-mat {
    border: 1px solid #E5E5E5;
    border-radius: 30px;
    background-color: #F7F7FA;
}

.keyword-tag {
    font-size: 0.9rem;
    background-color: #E1E1E1;
    font-weight: bold;
}

.keyword-tag-whole {
    background-color: #E1E1E1;
    border: 2px solid #E1E1E1;
}

.keyword-tag-partial {
    border: 2px dashed #E1E1E1;
    background-color: #fff;
}

.keyword-tag-whole-times {
    color: var(--muted);
}

.keyword-tag-partial-times {
    color: var(--muted);
}

.filter-action-options .custom-control {
    padding-left: 2rem;
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #6c7cff;
    border-color: #6c7cff;
}

.wizard-mode .keyword-item .is-invalid {
    background-position: right calc(0.375em + 0.5rem) center !important;
}


body.modal-open {
    overflow: hidden;
    position: fixed;
    width: 100%;
}

.modal-dialog-scrollable .modal-body {
    overflow-y: auto !important;
    max-height: 70vh !important;
}

.modal-dialog-scrollable .modal-content {
    max-height: 85vh;
}

.slide-fade-enter-active {
    transition: all .1s;
}

.slide-fade-leave-active {
    transition: all .1s;
}

.slide-fade-enter, .slide-fade-leave-to {
    transform: translateX(10px);
    opacity: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>
