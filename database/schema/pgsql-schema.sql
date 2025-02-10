--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2 (Debian 17.2-1.pgdg120+1)
-- Dumped by pg_dump version 17.2 (Debian 17.2-1.pgdg120+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_interstitials; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_interstitials (
    id bigint NOT NULL,
    user_id integer,
    type character varying(191),
    view character varying(191),
    item_id bigint,
    item_type character varying(191),
    has_media boolean DEFAULT false,
    blurhash character varying(191),
    message text,
    violation_header text,
    violation_body text,
    meta json,
    appeal_message text,
    appeal_requested_at timestamp(0) without time zone,
    appeal_handled_at timestamp(0) without time zone,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    severity_index smallint,
    is_spam boolean,
    in_violation boolean,
    violation_id integer,
    email_notify boolean,
    thread_id bigint,
    emailed_at timestamp(0) without time zone
);


--
-- Name: account_interstitials_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_interstitials_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_interstitials_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_interstitials_id_seq OWNED BY public.account_interstitials.id;


--
-- Name: account_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    item_id bigint,
    item_type character varying(191),
    action character varying(191),
    message character varying(191),
    link character varying(191),
    ip_address character varying(191),
    user_agent character varying(191),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: account_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_logs_id_seq OWNED BY public.account_logs.id;


--
-- Name: activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activities (
    id bigint NOT NULL,
    to_id bigint,
    from_id bigint,
    object_type character varying(191),
    data json,
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activities_id_seq OWNED BY public.activities.id;


--
-- Name: admin_invites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_invites (
    id bigint NOT NULL,
    name character varying(191),
    invite_code character varying(191) NOT NULL,
    description text,
    message text,
    max_uses integer,
    uses integer DEFAULT 0 NOT NULL,
    skip_email_verification boolean DEFAULT false NOT NULL,
    expires_at timestamp(0) without time zone,
    used_by json,
    admin_user_id integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: admin_invites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_invites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_invites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_invites_id_seq OWNED BY public.admin_invites.id;


--
-- Name: admin_shadow_filters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_shadow_filters (
    id bigint NOT NULL,
    admin_id bigint,
    item_type character varying(191) NOT NULL,
    item_id bigint NOT NULL,
    is_local boolean DEFAULT true NOT NULL,
    note text,
    active boolean DEFAULT false NOT NULL,
    history json,
    ruleset json,
    prevent_ap_fanout boolean DEFAULT false NOT NULL,
    prevent_new_dms boolean DEFAULT false NOT NULL,
    ignore_reports boolean DEFAULT false NOT NULL,
    ignore_mentions boolean DEFAULT false NOT NULL,
    ignore_links boolean DEFAULT false NOT NULL,
    ignore_hashtags boolean DEFAULT false NOT NULL,
    hide_from_public_feeds boolean DEFAULT false NOT NULL,
    hide_from_tag_feeds boolean DEFAULT false NOT NULL,
    hide_embeds boolean DEFAULT false NOT NULL,
    hide_from_story_carousel boolean DEFAULT false NOT NULL,
    hide_from_search_autocomplete boolean DEFAULT false NOT NULL,
    hide_from_search boolean DEFAULT false NOT NULL,
    requires_login boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: admin_shadow_filters_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_shadow_filters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_shadow_filters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_shadow_filters_id_seq OWNED BY public.admin_shadow_filters.id;


--
-- Name: app_registers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.app_registers (
    id bigint NOT NULL,
    email character varying(191) NOT NULL,
    verify_code character varying(191) NOT NULL,
    email_delivered_at timestamp(0) without time zone,
    email_verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: app_registers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.app_registers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: app_registers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.app_registers_id_seq OWNED BY public.app_registers.id;


--
-- Name: autospam_custom_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.autospam_custom_tokens (
    id bigint NOT NULL,
    token character varying(191) NOT NULL,
    weight integer DEFAULT 1 NOT NULL,
    is_spam boolean DEFAULT true NOT NULL,
    note text,
    category character varying(191),
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: autospam_custom_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.autospam_custom_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: autospam_custom_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.autospam_custom_tokens_id_seq OWNED BY public.autospam_custom_tokens.id;


--
-- Name: avatars; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.avatars (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    media_path character varying(191),
    change_count integer DEFAULT 0 NOT NULL,
    last_processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    remote_url character varying(191),
    last_fetched_at timestamp(0) without time zone,
    cdn_url character varying(191),
    size integer,
    is_remote boolean
);


--
-- Name: avatars_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.avatars_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: avatars_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.avatars_id_seq OWNED BY public.avatars.id;


--
-- Name: bookmarks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bookmarks (
    id integer NOT NULL,
    status_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bookmarks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bookmarks_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bookmarks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bookmarks_id_seq OWNED BY public.bookmarks.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(191) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(191) NOT NULL,
    owner character varying(191) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: circle_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.circle_profiles (
    id bigint NOT NULL,
    owner_id bigint,
    circle_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: circle_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.circle_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: circle_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.circle_profiles_id_seq OWNED BY public.circle_profiles.id;


--
-- Name: circles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.circles (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    name character varying(191),
    description text,
    scope character varying(191) DEFAULT 'public'::character varying NOT NULL,
    bcc boolean DEFAULT false NOT NULL,
    active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: circles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.circles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: circles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.circles_id_seq OWNED BY public.circles.id;


--
-- Name: collection_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collection_items (
    id bigint NOT NULL,
    collection_id bigint NOT NULL,
    "order" integer,
    object_type character varying(191) DEFAULT 'post'::character varying NOT NULL,
    object_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: collection_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collection_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collection_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collection_items_id_seq OWNED BY public.collection_items.id;


--
-- Name: collections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collections (
    id bigint NOT NULL,
    profile_id bigint,
    title character varying(191),
    description text,
    is_nsfw boolean DEFAULT false NOT NULL,
    visibility character varying(191) DEFAULT 'public'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: collections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collections_id_seq OWNED BY public.collections.id;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comments (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    user_id bigint,
    status_id bigint NOT NULL,
    comment text,
    rendered text,
    entities json,
    is_remote boolean DEFAULT false NOT NULL,
    rendered_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.comments_id_seq OWNED BY public.comments.id;


--
-- Name: config_cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.config_cache (
    id bigint NOT NULL,
    k character varying(191) NOT NULL,
    v text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: config_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.config_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: config_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.config_cache_id_seq OWNED BY public.config_cache.id;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    response_requested boolean DEFAULT false NOT NULL,
    message text NOT NULL,
    response text NOT NULL,
    read_at timestamp(0) without time zone,
    responded_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversations (
    id bigint NOT NULL,
    to_id bigint NOT NULL,
    from_id bigint NOT NULL,
    dm_id bigint,
    status_id bigint,
    type character varying(191),
    is_hidden boolean DEFAULT false NOT NULL,
    has_seen boolean DEFAULT false NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: conversations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.conversations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: conversations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.conversations_id_seq OWNED BY public.conversations.id;


--
-- Name: curated_register_activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curated_register_activities (
    id bigint NOT NULL,
    register_id integer,
    admin_id integer,
    reply_to_id integer,
    secret_code character varying(191),
    type character varying(191),
    title character varying(191),
    link character varying(191),
    message text,
    metadata json,
    from_admin boolean DEFAULT false NOT NULL,
    from_user boolean DEFAULT false NOT NULL,
    admin_only_view boolean DEFAULT true NOT NULL,
    action_required boolean DEFAULT false NOT NULL,
    admin_notified_at timestamp(0) without time zone,
    action_taken_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: curated_register_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.curated_register_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: curated_register_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.curated_register_activities_id_seq OWNED BY public.curated_register_activities.id;


--
-- Name: curated_register_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curated_register_templates (
    id bigint NOT NULL,
    name character varying(191),
    description text,
    content text,
    is_active boolean DEFAULT false NOT NULL,
    "order" smallint DEFAULT '10'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: curated_register_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.curated_register_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: curated_register_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.curated_register_templates_id_seq OWNED BY public.curated_register_templates.id;


--
-- Name: curated_registers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curated_registers (
    id bigint NOT NULL,
    email character varying(191),
    username character varying(191),
    password character varying(191),
    ip_address character varying(191),
    verify_code character varying(191),
    reason_to_join text,
    invited_by bigint,
    is_approved boolean DEFAULT false NOT NULL,
    is_rejected boolean DEFAULT false NOT NULL,
    is_awaiting_more_info boolean DEFAULT false NOT NULL,
    is_closed boolean DEFAULT false NOT NULL,
    autofollow_account_ids json,
    admin_notes json,
    approved_by_admin_id integer,
    email_verified_at timestamp(0) without time zone,
    admin_notified_at timestamp(0) without time zone,
    action_taken_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    user_has_responded boolean DEFAULT false NOT NULL
);


--
-- Name: curated_registers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.curated_registers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: curated_registers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.curated_registers_id_seq OWNED BY public.curated_registers.id;


--
-- Name: custom_emoji; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_emoji (
    id bigint NOT NULL,
    shortcode character varying(191) NOT NULL,
    media_path character varying(191),
    domain character varying(191),
    disabled boolean DEFAULT false NOT NULL,
    uri character varying(191),
    image_remote_url character varying(191),
    category_id integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: custom_emoji_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_emoji_categories (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    disabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: custom_emoji_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.custom_emoji_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: custom_emoji_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.custom_emoji_categories_id_seq OWNED BY public.custom_emoji_categories.id;


--
-- Name: custom_emoji_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.custom_emoji_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: custom_emoji_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.custom_emoji_id_seq OWNED BY public.custom_emoji.id;


--
-- Name: default_domain_blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.default_domain_blocks (
    id bigint NOT NULL,
    domain character varying(191) NOT NULL,
    note text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: default_domain_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.default_domain_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: default_domain_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.default_domain_blocks_id_seq OWNED BY public.default_domain_blocks.id;


--
-- Name: direct_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.direct_messages (
    id bigint NOT NULL,
    to_id bigint NOT NULL,
    from_id bigint NOT NULL,
    from_profile_ids character varying(191),
    group_message boolean DEFAULT false NOT NULL,
    status_id bigint NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(191) DEFAULT 'text'::character varying,
    is_hidden boolean DEFAULT false NOT NULL,
    meta json
);


--
-- Name: direct_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.direct_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: direct_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.direct_messages_id_seq OWNED BY public.direct_messages.id;


--
-- Name: discover_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.discover_categories (
    id bigint NOT NULL,
    name character varying(191),
    slug character varying(191) NOT NULL,
    active boolean DEFAULT false NOT NULL,
    "order" smallint DEFAULT '5'::smallint NOT NULL,
    media_id bigint,
    no_nsfw boolean DEFAULT true NOT NULL,
    local_only boolean DEFAULT true NOT NULL,
    public_only boolean DEFAULT true NOT NULL,
    photos_only boolean DEFAULT true NOT NULL,
    active_until timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: discover_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.discover_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: discover_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.discover_categories_id_seq OWNED BY public.discover_categories.id;


--
-- Name: discover_category_hashtags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.discover_category_hashtags (
    id bigint NOT NULL,
    discover_category_id bigint NOT NULL,
    hashtag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: discover_category_hashtags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.discover_category_hashtags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: discover_category_hashtags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.discover_category_hashtags_id_seq OWNED BY public.discover_category_hashtags.id;


--
-- Name: email_verifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_verifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    email character varying(191),
    user_token character varying(191) NOT NULL,
    random_token character varying(191) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: email_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_verifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_verifications_id_seq OWNED BY public.email_verifications.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    uuid character varying(191)
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: follow_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.follow_requests (
    id bigint NOT NULL,
    follower_id bigint NOT NULL,
    following_id bigint NOT NULL,
    is_rejected boolean DEFAULT false NOT NULL,
    is_local boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    activity json,
    handled_at timestamp(0) without time zone
);


--
-- Name: follow_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.follow_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: follow_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.follow_requests_id_seq OWNED BY public.follow_requests.id;


--
-- Name: followers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.followers (
    id integer NOT NULL,
    profile_id bigint NOT NULL,
    following_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    local_profile boolean DEFAULT true NOT NULL,
    local_following boolean DEFAULT true NOT NULL,
    show_reblogs boolean DEFAULT true NOT NULL,
    notify boolean DEFAULT false NOT NULL
);


--
-- Name: followers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.followers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: followers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.followers_id_seq OWNED BY public.followers.id;


--
-- Name: group_activity_graphs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_activity_graphs (
    id bigint NOT NULL,
    instance_id bigint,
    actor_id bigint,
    verb character varying(191),
    id_url character varying(191),
    payload json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_activity_graphs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_activity_graphs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_activity_graphs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_activity_graphs_id_seq OWNED BY public.group_activity_graphs.id;


--
-- Name: group_blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_blocks (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    admin_id bigint,
    profile_id bigint,
    instance_id bigint,
    name character varying(191),
    reason character varying(191),
    is_user boolean NOT NULL,
    moderated boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_blocks_id_seq OWNED BY public.group_blocks.id;


--
-- Name: group_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_categories (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    slug character varying(191) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    "order" smallint,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_categories_id_seq OWNED BY public.group_categories.id;


--
-- Name: group_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_comments (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint,
    status_id bigint,
    in_reply_to_id bigint,
    remote_url character varying(191),
    caption text,
    is_nsfw boolean DEFAULT false NOT NULL,
    visibility character varying(191),
    likes_count integer DEFAULT 0 NOT NULL,
    replies_count integer DEFAULT 0 NOT NULL,
    cw_summary text,
    media_ids json,
    status character varying(191),
    type character varying(191) DEFAULT 'text'::character varying,
    local boolean DEFAULT false NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_comments_id_seq OWNED BY public.group_comments.id;


--
-- Name: group_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_events (
    id bigint NOT NULL,
    group_id bigint,
    profile_id bigint,
    name character varying(191),
    type character varying(191) NOT NULL,
    tags json,
    location json,
    description text,
    metadata json,
    open boolean DEFAULT false NOT NULL,
    comments_open boolean DEFAULT false NOT NULL,
    show_guest_list boolean DEFAULT false NOT NULL,
    start_at timestamp(0) without time zone,
    end_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_events_id_seq OWNED BY public.group_events.id;


--
-- Name: group_hashtags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_hashtags (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    formatted character varying(191),
    recommended boolean DEFAULT false NOT NULL,
    sensitive boolean DEFAULT false NOT NULL,
    banned boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_hashtags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_hashtags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_hashtags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_hashtags_id_seq OWNED BY public.group_hashtags.id;


--
-- Name: group_interactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_interactions (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    type character varying(191),
    item_type character varying(191),
    item_id character varying(191),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_interactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_interactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_interactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_interactions_id_seq OWNED BY public.group_interactions.id;


--
-- Name: group_invitations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_invitations (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    from_profile_id bigint NOT NULL,
    to_profile_id bigint NOT NULL,
    role character varying(191),
    to_local boolean DEFAULT true NOT NULL,
    from_local boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_invitations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_invitations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_invitations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_invitations_id_seq OWNED BY public.group_invitations.id;


--
-- Name: group_likes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_likes (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    status_id bigint,
    comment_id bigint,
    local boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_likes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_likes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_likes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_likes_id_seq OWNED BY public.group_likes.id;


--
-- Name: group_limits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_limits (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    limits json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_limits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_limits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_limits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_limits_id_seq OWNED BY public.group_limits.id;


--
-- Name: group_media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_media (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    status_id bigint,
    media_path character varying(191) NOT NULL,
    thumbnail_url text,
    cdn_url text,
    url text,
    mime character varying(191),
    size integer,
    cw_summary text,
    license character varying(191),
    blurhash character varying(191),
    "order" smallint DEFAULT '1'::smallint NOT NULL,
    width integer,
    height integer,
    local_user boolean DEFAULT true NOT NULL,
    is_cached boolean DEFAULT false NOT NULL,
    is_comment boolean DEFAULT false NOT NULL,
    metadata json,
    version character varying(191) DEFAULT '1'::character varying NOT NULL,
    skip_optimize boolean DEFAULT false NOT NULL,
    processed_at timestamp(0) without time zone,
    thumbnail_generated timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_media_id_seq OWNED BY public.group_media.id;


--
-- Name: group_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_members (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    role character varying(191) DEFAULT 'member'::character varying NOT NULL,
    local_group boolean DEFAULT false NOT NULL,
    local_profile boolean DEFAULT false NOT NULL,
    join_request boolean DEFAULT false NOT NULL,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_members_id_seq OWNED BY public.group_members.id;


--
-- Name: group_post_hashtags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_post_hashtags (
    id bigint NOT NULL,
    hashtag_id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    status_id bigint,
    status_visibility character varying(191),
    nsfw boolean DEFAULT false NOT NULL
);


--
-- Name: group_post_hashtags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_post_hashtags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_post_hashtags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_post_hashtags_id_seq OWNED BY public.group_post_hashtags.id;


--
-- Name: group_posts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_posts (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint,
    type character varying(191),
    remote_url character varying(191),
    reply_count integer,
    status character varying(191),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    caption text,
    visibility character varying(191),
    is_nsfw boolean DEFAULT false NOT NULL,
    likes_count integer DEFAULT 0 NOT NULL,
    cw_summary text,
    media_ids json,
    comments_disabled boolean DEFAULT false NOT NULL
);


--
-- Name: group_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_reports (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    type character varying(191),
    item_type character varying(191),
    item_id character varying(191),
    metadata json,
    open boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_reports_id_seq OWNED BY public.group_reports.id;


--
-- Name: group_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_roles (
    id bigint NOT NULL,
    group_id bigint NOT NULL,
    name character varying(191) NOT NULL,
    slug character varying(191),
    abilities text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_roles_id_seq OWNED BY public.group_roles.id;


--
-- Name: group_stores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_stores (
    id bigint NOT NULL,
    group_id bigint,
    store_key character varying(191) NOT NULL,
    store_value json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_stores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_stores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_stores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_stores_id_seq OWNED BY public.group_stores.id;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.groups (
    id bigint NOT NULL,
    profile_id bigint,
    status character varying(191),
    name character varying(191),
    description text,
    rules text,
    local boolean DEFAULT true NOT NULL,
    remote_url character varying(191),
    inbox_url character varying(191),
    is_private boolean DEFAULT false NOT NULL,
    local_only boolean DEFAULT false NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    category_id integer DEFAULT 1 NOT NULL,
    member_count integer,
    recommended boolean DEFAULT false NOT NULL,
    discoverable boolean DEFAULT false NOT NULL,
    activitypub boolean DEFAULT false NOT NULL,
    is_nsfw boolean DEFAULT false NOT NULL,
    dms boolean DEFAULT false NOT NULL,
    autospam boolean DEFAULT false NOT NULL,
    verified boolean DEFAULT false NOT NULL,
    last_active_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: hashtag_follows; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hashtag_follows (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    hashtag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hashtag_follows_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hashtag_follows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hashtag_follows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hashtag_follows_id_seq OWNED BY public.hashtag_follows.id;


--
-- Name: hashtag_related; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hashtag_related (
    id bigint NOT NULL,
    hashtag_id bigint NOT NULL,
    related_tags json,
    agg_score bigint,
    last_calculated_at timestamp(0) without time zone,
    last_moderated_at timestamp(0) without time zone,
    skip_refresh boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hashtag_related_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hashtag_related_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hashtag_related_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hashtag_related_id_seq OWNED BY public.hashtag_related.id;


--
-- Name: hashtags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hashtags (
    id bigint NOT NULL,
    name character varying(191) NOT NULL,
    slug character varying(191) NOT NULL,
    is_nsfw boolean DEFAULT false NOT NULL,
    is_banned boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    cached_count integer,
    can_trend boolean,
    can_search boolean
);


--
-- Name: hashtags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hashtags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hashtags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hashtags_id_seq OWNED BY public.hashtags.id;


--
-- Name: import_datas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_datas (
    id integer NOT NULL,
    profile_id bigint NOT NULL,
    service character varying(191) DEFAULT 'instagram'::character varying NOT NULL,
    path character varying(191),
    stage smallint DEFAULT '1'::smallint NOT NULL,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    job_id bigint,
    original_name character varying(191),
    import_accepted boolean DEFAULT false
);


--
-- Name: import_datas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_datas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_datas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_datas_id_seq OWNED BY public.import_datas.id;


--
-- Name: import_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_jobs (
    id integer NOT NULL,
    profile_id bigint NOT NULL,
    service character varying(191) DEFAULT 'instagram'::character varying NOT NULL,
    uuid character varying(191),
    storage_path character varying(191),
    stage smallint DEFAULT '0'::smallint NOT NULL,
    media_json text,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: import_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_jobs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_jobs_id_seq OWNED BY public.import_jobs.id;


--
-- Name: import_posts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_posts (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    user_id integer NOT NULL,
    service character varying(191) NOT NULL,
    post_hash character varying(191),
    filename character varying(191) NOT NULL,
    media_count smallint NOT NULL,
    post_type character varying(191),
    caption text,
    media json,
    creation_year smallint,
    creation_month smallint,
    creation_day smallint,
    creation_id smallint,
    status_id bigint,
    creation_date timestamp(0) without time zone,
    metadata json,
    skip_missing_media boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    uploaded_to_s3 boolean DEFAULT false NOT NULL
);


--
-- Name: import_posts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_posts_id_seq OWNED BY public.import_posts.id;


--
-- Name: instance_actors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instance_actors (
    id bigint NOT NULL,
    private_key text,
    public_key text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: instance_actors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instance_actors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instance_actors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instance_actors_id_seq OWNED BY public.instance_actors.id;


--
-- Name: instances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instances (
    id bigint NOT NULL,
    domain character varying(191) NOT NULL,
    url character varying(191),
    name character varying(191),
    admin_url character varying(191),
    limit_reason character varying(191),
    unlisted boolean DEFAULT false NOT NULL,
    auto_cw boolean DEFAULT false NOT NULL,
    banned boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    software character varying(191),
    user_count integer,
    status_count integer,
    last_crawled_at timestamp(0) without time zone,
    actors_last_synced_at timestamp(0) without time zone,
    notes text,
    manually_added boolean DEFAULT false NOT NULL,
    base_domain character varying(191),
    ban_subdomains boolean,
    ip_address character varying(191),
    list_limitation boolean DEFAULT false NOT NULL,
    active_deliver boolean,
    valid_nodeinfo boolean,
    nodeinfo_last_fetched timestamp(0) without time zone,
    delivery_timeout boolean DEFAULT false NOT NULL,
    delivery_next_after timestamp(0) without time zone,
    shared_inbox character varying(191)
);


--
-- Name: instances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instances_id_seq OWNED BY public.instances.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(191) NOT NULL,
    name character varying(191) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(191) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: likes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.likes (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    status_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    status_profile_id bigint,
    is_comment boolean
);


--
-- Name: likes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.likes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: likes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.likes_id_seq OWNED BY public.likes.id;


--
-- Name: live_streams; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.live_streams (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    stream_id character varying(191),
    stream_key character varying(191),
    visibility character varying(191),
    name character varying(191),
    description text,
    thumbnail_path character varying(191),
    settings json,
    live_chat boolean DEFAULT true NOT NULL,
    mod_ids json,
    discoverable boolean,
    live_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: live_streams_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.live_streams_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: live_streams_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.live_streams_id_seq OWNED BY public.live_streams.id;


--
-- Name: login_links; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_links (
    id bigint NOT NULL,
    key character varying(191) NOT NULL,
    secret character varying(191) NOT NULL,
    user_id integer NOT NULL,
    ip character varying(191),
    user_agent character varying(191),
    meta json,
    revoked_at timestamp(0) without time zone,
    resent_at timestamp(0) without time zone,
    used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: login_links_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_links_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_links_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_links_id_seq OWNED BY public.login_links.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media (
    id integer NOT NULL,
    status_id bigint,
    profile_id bigint,
    user_id bigint,
    media_path character varying(191) NOT NULL,
    thumbnail_path character varying(191),
    cdn_url text,
    optimized_url character varying(191),
    thumbnail_url character varying(191),
    "order" smallint DEFAULT '1'::smallint NOT NULL,
    mime character varying(191),
    size integer,
    orientation character varying(191),
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    filter_name character varying(191),
    filter_class character varying(191),
    deleted_at timestamp(0) without time zone,
    original_sha256 character varying(191),
    optimized_sha256 character varying(191),
    caption text,
    hls_path character varying(191),
    hls_transcoded_at timestamp(0) without time zone,
    key character varying(191),
    metadata json,
    license character varying(191),
    is_nsfw boolean DEFAULT false NOT NULL,
    version smallint DEFAULT '1'::smallint NOT NULL,
    remote_media boolean DEFAULT false NOT NULL,
    remote_url character varying(191),
    blurhash character varying(191),
    srcset json,
    width integer,
    height integer,
    skip_optimize boolean,
    replicated_at timestamp(0) without time zone
);


--
-- Name: media_blocklists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media_blocklists (
    id bigint NOT NULL,
    sha256 character varying(191),
    sha512 character varying(191),
    name character varying(191),
    description text,
    active boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: media_blocklists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_blocklists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_blocklists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_blocklists_id_seq OWNED BY public.media_blocklists.id;


--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: media_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media_tags (
    id bigint NOT NULL,
    status_id bigint,
    media_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    tagged_username character varying(191),
    is_public boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: media_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_tags_id_seq OWNED BY public.media_tags.id;


--
-- Name: mentions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mentions (
    id bigint NOT NULL,
    status_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    local boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: mentions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mentions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mentions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mentions_id_seq OWNED BY public.mentions.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(191) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: mod_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mod_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    user_username character varying(191),
    object_uid bigint,
    object_id bigint,
    object_type character varying(191),
    action character varying(191),
    message text,
    metadata json,
    access_level character varying(191) DEFAULT 'admin'::character varying,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mod_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mod_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mod_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mod_logs_id_seq OWNED BY public.mod_logs.id;


--
-- Name: moderated_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.moderated_profiles (
    id bigint NOT NULL,
    profile_url character varying(191),
    profile_id bigint,
    domain character varying(191),
    note text,
    is_banned boolean DEFAULT false NOT NULL,
    is_nsfw boolean DEFAULT false NOT NULL,
    is_unlisted boolean DEFAULT false NOT NULL,
    is_noautolink boolean DEFAULT false NOT NULL,
    is_nodms boolean DEFAULT false NOT NULL,
    is_notrending boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: moderated_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.moderated_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: moderated_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.moderated_profiles_id_seq OWNED BY public.moderated_profiles.id;


--
-- Name: newsroom; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.newsroom (
    id bigint NOT NULL,
    user_id bigint,
    header_photo_url character varying(191),
    title character varying(191),
    slug character varying(191),
    category character varying(191) DEFAULT 'update'::character varying NOT NULL,
    summary text,
    body text,
    body_rendered text,
    link character varying(191),
    force_modal boolean DEFAULT false NOT NULL,
    show_timeline boolean DEFAULT false NOT NULL,
    show_link boolean DEFAULT false NOT NULL,
    auth_only boolean DEFAULT true NOT NULL,
    published_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: newsroom_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.newsroom_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: newsroom_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.newsroom_id_seq OWNED BY public.newsroom.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    actor_id bigint,
    action character varying(191),
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    item_id bigint,
    item_type character varying(191),
    deleted_at timestamp(0) without time zone
);


--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: oauth_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_access_tokens (
    id character varying(100) NOT NULL,
    user_id bigint,
    client_id bigint NOT NULL,
    name character varying(191),
    scopes text,
    revoked boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone
);


--
-- Name: oauth_auth_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_auth_codes (
    id character varying(100) NOT NULL,
    user_id bigint NOT NULL,
    client_id bigint NOT NULL,
    scopes text,
    revoked boolean NOT NULL,
    expires_at timestamp(0) without time zone
);


--
-- Name: oauth_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_clients (
    id bigint NOT NULL,
    user_id bigint,
    name character varying(191) NOT NULL,
    secret character varying(100),
    provider character varying(191),
    redirect text NOT NULL,
    personal_access_client boolean NOT NULL,
    password_client boolean NOT NULL,
    revoked boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: oauth_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oauth_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oauth_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oauth_clients_id_seq OWNED BY public.oauth_clients.id;


--
-- Name: oauth_personal_access_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_personal_access_clients (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: oauth_personal_access_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oauth_personal_access_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oauth_personal_access_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oauth_personal_access_clients_id_seq OWNED BY public.oauth_personal_access_clients.id;


--
-- Name: oauth_refresh_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oauth_refresh_tokens (
    id character varying(100) NOT NULL,
    access_token_id character varying(100) NOT NULL,
    revoked boolean NOT NULL,
    expires_at timestamp(0) without time zone
);


--
-- Name: pages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pages (
    id bigint NOT NULL,
    root character varying(191),
    slug character varying(191),
    title character varying(191),
    category_id integer,
    content text,
    template character varying(191) DEFAULT 'layouts.app'::character varying NOT NULL,
    active boolean DEFAULT false NOT NULL,
    cached boolean DEFAULT true NOT NULL,
    active_until timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pages_id_seq OWNED BY public.pages.id;


--
-- Name: parental_controls; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.parental_controls (
    id bigint NOT NULL,
    parent_id integer NOT NULL,
    child_id integer,
    email character varying(191),
    verify_code character varying(191),
    email_sent_at timestamp(0) without time zone,
    email_verified_at timestamp(0) without time zone,
    permissions json,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: parental_controls_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.parental_controls_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: parental_controls_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.parental_controls_id_seq OWNED BY public.parental_controls.id;


--
-- Name: password_resets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_resets (
    email character varying(191) NOT NULL,
    token character varying(191) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: places; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.places (
    id bigint NOT NULL,
    slug character varying(191) NOT NULL,
    name character varying(191) NOT NULL,
    country character varying(191) NOT NULL,
    aliases json,
    lat numeric(9,6),
    long numeric(9,6),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    state character varying(191),
    score smallint DEFAULT '0'::smallint NOT NULL,
    cached_post_count bigint,
    last_checked_at timestamp(0) without time zone
);


--
-- Name: places_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.places_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: places_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.places_id_seq OWNED BY public.places.id;


--
-- Name: poll_votes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.poll_votes (
    id bigint NOT NULL,
    story_id bigint,
    status_id bigint,
    profile_id bigint NOT NULL,
    poll_id bigint NOT NULL,
    choice integer DEFAULT 0 NOT NULL,
    uri character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: poll_votes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.poll_votes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: poll_votes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.poll_votes_id_seq OWNED BY public.poll_votes.id;


--
-- Name: polls; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.polls (
    id bigint NOT NULL,
    story_id bigint,
    status_id bigint,
    group_id bigint,
    profile_id bigint NOT NULL,
    poll_options json,
    cached_tallies json,
    multiple boolean DEFAULT false NOT NULL,
    hide_totals boolean DEFAULT false NOT NULL,
    votes_count integer DEFAULT 0 NOT NULL,
    last_fetched_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: portfolios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.portfolios (
    id bigint NOT NULL,
    user_id integer,
    profile_id bigint NOT NULL,
    active boolean,
    show_captions boolean DEFAULT true,
    show_license boolean DEFAULT true,
    show_location boolean DEFAULT true,
    show_timestamp boolean DEFAULT true,
    show_link boolean DEFAULT true,
    profile_source character varying(191) DEFAULT 'recent'::character varying,
    show_avatar boolean DEFAULT true,
    show_bio boolean DEFAULT true,
    profile_layout character varying(191) DEFAULT 'grid'::character varying,
    profile_container character varying(191) DEFAULT 'fixed'::character varying,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: portfolios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.portfolios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: portfolios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.portfolios_id_seq OWNED BY public.portfolios.id;


--
-- Name: profile_aliases; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profile_aliases (
    id bigint NOT NULL,
    profile_id bigint,
    acct character varying(191),
    uri character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: profile_aliases_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.profile_aliases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: profile_aliases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.profile_aliases_id_seq OWNED BY public.profile_aliases.id;


--
-- Name: profile_migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profile_migrations (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    acct character varying(191),
    followers_count bigint DEFAULT '0'::bigint NOT NULL,
    target_profile_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: profile_migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.profile_migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: profile_migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.profile_migrations_id_seq OWNED BY public.profile_migrations.id;


--
-- Name: profile_sponsors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profile_sponsors (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    sponsors json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: profile_sponsors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.profile_sponsors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: profile_sponsors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.profile_sponsors_id_seq OWNED BY public.profile_sponsors.id;


--
-- Name: profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profiles (
    id bigint NOT NULL,
    user_id integer,
    domain character varying(191),
    username character varying(191),
    name character varying(191),
    bio text,
    location character varying(191),
    website character varying(191),
    is_private boolean DEFAULT false NOT NULL,
    "sharedInbox" character varying(191),
    private_key text,
    public_key text,
    remote_url character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    inbox_url character varying(191),
    outbox_url character varying(191),
    follower_url character varying(191),
    following_url character varying(191),
    key_id character varying(191),
    status character varying(191),
    delete_after timestamp(0) without time zone,
    unlisted boolean DEFAULT false NOT NULL,
    cw boolean DEFAULT false NOT NULL,
    no_autolink boolean DEFAULT false NOT NULL,
    profile_layout character varying(191),
    post_layout character varying(191),
    is_suggestable boolean DEFAULT false NOT NULL,
    header_bg character varying(191),
    last_fetched_at timestamp(0) without time zone,
    status_count integer DEFAULT 0,
    followers_count integer DEFAULT 0,
    following_count integer DEFAULT 0,
    webfinger character varying(191),
    avatar_url character varying(191),
    last_status_at timestamp(0) without time zone,
    moved_to_profile_id bigint,
    indexable boolean DEFAULT false NOT NULL
);


--
-- Name: profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.profiles_id_seq OWNED BY public.profiles.id;


--
-- Name: pulse_aggregates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pulse_aggregates (
    id bigint NOT NULL,
    bucket integer NOT NULL,
    period integer NOT NULL,
    type character varying(191) NOT NULL,
    key text NOT NULL,
    key_hash uuid GENERATED ALWAYS AS ((md5(key))::uuid) STORED NOT NULL,
    aggregate character varying(191) NOT NULL,
    value numeric(20,2) NOT NULL,
    count integer
);


--
-- Name: pulse_aggregates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pulse_aggregates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pulse_aggregates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pulse_aggregates_id_seq OWNED BY public.pulse_aggregates.id;


--
-- Name: pulse_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pulse_entries (
    id bigint NOT NULL,
    "timestamp" integer NOT NULL,
    type character varying(191) NOT NULL,
    key text NOT NULL,
    key_hash uuid GENERATED ALWAYS AS ((md5(key))::uuid) STORED NOT NULL,
    value bigint
);


--
-- Name: pulse_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pulse_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pulse_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pulse_entries_id_seq OWNED BY public.pulse_entries.id;


--
-- Name: pulse_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pulse_values (
    id bigint NOT NULL,
    "timestamp" integer NOT NULL,
    type character varying(191) NOT NULL,
    key text NOT NULL,
    key_hash uuid GENERATED ALWAYS AS ((md5(key))::uuid) STORED NOT NULL,
    value text NOT NULL
);


--
-- Name: pulse_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pulse_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pulse_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pulse_values_id_seq OWNED BY public.pulse_values.id;


--
-- Name: push_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_subscriptions (
    id bigint NOT NULL,
    subscribable_type character varying(191) NOT NULL,
    subscribable_id bigint NOT NULL,
    endpoint character varying(500) NOT NULL,
    public_key character varying(191),
    auth_token character varying(191),
    content_encoding character varying(191),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.push_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.push_subscriptions_id_seq OWNED BY public.push_subscriptions.id;


--
-- Name: remote_auth_instances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.remote_auth_instances (
    id bigint NOT NULL,
    domain character varying(191),
    instance_id integer,
    client_id character varying(191),
    client_secret character varying(191),
    redirect_uri character varying(191),
    root_domain character varying(191),
    allowed boolean,
    banned boolean DEFAULT false NOT NULL,
    active boolean DEFAULT true NOT NULL,
    last_refreshed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: remote_auth_instances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.remote_auth_instances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: remote_auth_instances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.remote_auth_instances_id_seq OWNED BY public.remote_auth_instances.id;


--
-- Name: remote_auths; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.remote_auths (
    id bigint NOT NULL,
    software character varying(191),
    domain character varying(191),
    webfinger character varying(191),
    instance_id integer,
    user_id integer,
    client_id integer,
    ip_address character varying(191),
    bearer_token text,
    verify_credentials json,
    last_successful_login_at timestamp(0) without time zone,
    last_verify_credentials_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: remote_auths_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.remote_auths_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: remote_auths_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.remote_auths_id_seq OWNED BY public.remote_auths.id;


--
-- Name: remote_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.remote_reports (
    id bigint NOT NULL,
    status_ids json,
    comment text,
    account_id bigint,
    uri character varying(191),
    instance_id integer,
    action_taken_at timestamp(0) without time zone,
    report_meta json,
    action_taken_meta json,
    action_taken_by_account_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: remote_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.remote_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: remote_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.remote_reports_id_seq OWNED BY public.remote_reports.id;


--
-- Name: report_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_comments (
    id integer NOT NULL,
    report_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    user_id bigint NOT NULL,
    comment text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: report_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_comments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_comments_id_seq OWNED BY public.report_comments.id;


--
-- Name: report_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_logs (
    id integer NOT NULL,
    profile_id bigint NOT NULL,
    item_id bigint,
    item_type character varying(191),
    action character varying(191),
    system_message boolean DEFAULT false NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: report_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_logs_id_seq OWNED BY public.report_logs.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reports (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    user_id bigint,
    object_id bigint NOT NULL,
    object_type character varying(191),
    reported_profile_id bigint,
    type character varying(191),
    message character varying(191),
    admin_seen timestamp(0) without time zone,
    not_interested boolean DEFAULT false NOT NULL,
    spam boolean DEFAULT false NOT NULL,
    nsfw boolean DEFAULT false NOT NULL,
    abusive boolean DEFAULT false NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(191) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: status_archiveds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_archiveds (
    id bigint NOT NULL,
    status_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    original_scope character varying(191),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: status_archiveds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.status_archiveds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: status_archiveds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.status_archiveds_id_seq OWNED BY public.status_archiveds.id;


--
-- Name: status_edits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_edits (
    id bigint NOT NULL,
    status_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    caption text,
    spoiler_text text,
    ordered_media_attachment_ids json,
    media_descriptions json,
    poll_options json,
    is_nsfw boolean,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: status_edits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.status_edits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: status_edits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.status_edits_id_seq OWNED BY public.status_edits.id;


--
-- Name: status_hashtags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_hashtags (
    id bigint NOT NULL,
    status_id bigint NOT NULL,
    hashtag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    profile_id bigint,
    status_visibility character varying(191)
);


--
-- Name: status_hashtags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.status_hashtags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: status_hashtags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.status_hashtags_id_seq OWNED BY public.status_hashtags.id;


--
-- Name: status_views; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_views (
    id bigint NOT NULL,
    status_id bigint,
    status_profile_id bigint,
    profile_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: status_views_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.status_views_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: status_views_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.status_views_id_seq OWNED BY public.status_views.id;


--
-- Name: statuses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.statuses (
    id bigint NOT NULL,
    uri character varying(191),
    caption text,
    rendered text,
    profile_id bigint,
    in_reply_to_id bigint,
    reblog_of_id bigint,
    url character varying(191),
    is_nsfw boolean DEFAULT false NOT NULL,
    visibility character varying(255) DEFAULT 'public'::character varying NOT NULL,
    reply boolean DEFAULT false NOT NULL,
    likes_count bigint DEFAULT '0'::bigint NOT NULL,
    reblogs_count bigint DEFAULT '0'::bigint NOT NULL,
    language character varying(191),
    conversation_id bigint,
    local boolean DEFAULT true NOT NULL,
    application_id bigint,
    in_reply_to_profile_id bigint,
    entities json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    cw_summary character varying(191),
    scope character varying(191) DEFAULT 'public'::character varying NOT NULL,
    type character varying(191),
    reply_count integer,
    comments_disabled boolean DEFAULT false NOT NULL,
    place_id bigint,
    object_url character varying(191),
    edited_at timestamp(0) without time zone,
    trendable boolean,
    media_ids json,
    CONSTRAINT statuses_visibility_check CHECK (((visibility)::text = ANY (ARRAY[('public'::character varying)::text, ('unlisted'::character varying)::text, ('private'::character varying)::text, ('direct'::character varying)::text, ('draft'::character varying)::text])))
);


--
-- Name: statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.statuses_id_seq OWNED BY public.statuses.id;


--
-- Name: stories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stories (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    type character varying(191),
    size integer,
    mime character varying(191),
    duration smallint NOT NULL,
    path character varying(191),
    cdn_url character varying(191),
    public boolean DEFAULT false NOT NULL,
    local boolean DEFAULT false NOT NULL,
    view_count integer,
    comment_count integer,
    story json,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    remote_url character varying(191),
    media_url character varying(191),
    is_archived boolean DEFAULT false,
    name character varying(191),
    active boolean,
    can_reply boolean DEFAULT true NOT NULL,
    can_react boolean DEFAULT true NOT NULL,
    object_id character varying(191),
    object_uri character varying(191),
    bearcap_token character varying(191)
);


--
-- Name: stories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stories_id_seq OWNED BY public.stories.id;


--
-- Name: story_views; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.story_views (
    id bigint NOT NULL,
    story_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: story_views_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.story_views_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: story_views_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.story_views_id_seq OWNED BY public.story_views.id;


--
-- Name: telescope_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_entries (
    sequence bigint NOT NULL,
    uuid uuid NOT NULL,
    batch_id uuid NOT NULL,
    family_hash character varying(191),
    should_display_on_index boolean DEFAULT true NOT NULL,
    type character varying(20) NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telescope_entries_sequence_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telescope_entries_sequence_seq OWNED BY public.telescope_entries.sequence;


--
-- Name: telescope_entries_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_entries_tags (
    entry_uuid uuid NOT NULL,
    tag character varying(191) NOT NULL
);


--
-- Name: telescope_monitoring; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_monitoring (
    tag character varying(191) NOT NULL
);


--
-- Name: uikit; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.uikit (
    id bigint NOT NULL,
    k character varying(191) NOT NULL,
    v text,
    meta json,
    defv text,
    dhis text,
    edit_count integer DEFAULT 0,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: uikit_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.uikit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: uikit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.uikit_id_seq OWNED BY public.uikit.id;


--
-- Name: user_app_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_app_settings (
    id bigint NOT NULL,
    user_id integer NOT NULL,
    profile_id bigint NOT NULL,
    common json,
    custom json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_app_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_app_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_app_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_app_settings_id_seq OWNED BY public.user_app_settings.id;


--
-- Name: user_devices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_devices (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    ip character varying(191) NOT NULL,
    user_agent character varying(191) NOT NULL,
    fingerprint character varying(191),
    name character varying(191),
    trusted boolean,
    last_active_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_devices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_devices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_devices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_devices_id_seq OWNED BY public.user_devices.id;


--
-- Name: user_domain_blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_domain_blocks (
    id bigint NOT NULL,
    profile_id bigint NOT NULL,
    domain character varying(191) NOT NULL
);


--
-- Name: user_domain_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_domain_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_domain_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_domain_blocks_id_seq OWNED BY public.user_domain_blocks.id;


--
-- Name: user_email_forgots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_email_forgots (
    id bigint NOT NULL,
    user_id integer NOT NULL,
    ip_address character varying(191),
    user_agent character varying(191),
    referrer character varying(191),
    email_sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_email_forgots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_email_forgots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_email_forgots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_email_forgots_id_seq OWNED BY public.user_email_forgots.id;


--
-- Name: user_filters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_filters (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    filterable_id bigint NOT NULL,
    filterable_type character varying(191) NOT NULL,
    filter_type character varying(191) DEFAULT 'block'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_filters_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_filters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_filters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_filters_id_seq OWNED BY public.user_filters.id;


--
-- Name: user_invites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_invites (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    profile_id bigint NOT NULL,
    email character varying(191) NOT NULL,
    message text,
    key character varying(191) NOT NULL,
    token character varying(191) NOT NULL,
    valid_until timestamp(0) without time zone,
    used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_invites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_invites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_invites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_invites_id_seq OWNED BY public.user_invites.id;


--
-- Name: user_pronouns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_pronouns (
    id bigint NOT NULL,
    user_id integer,
    profile_id bigint NOT NULL,
    pronouns json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_pronouns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_pronouns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_pronouns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_pronouns_id_seq OWNED BY public.user_pronouns.id;


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_roles (
    id bigint NOT NULL,
    profile_id bigint,
    user_id integer NOT NULL,
    roles json,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_roles_id_seq OWNED BY public.user_roles.id;


--
-- Name: user_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_settings (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(191) DEFAULT 'user'::character varying NOT NULL,
    crawlable boolean DEFAULT true NOT NULL,
    show_guests boolean DEFAULT true NOT NULL,
    show_discover boolean DEFAULT true NOT NULL,
    public_dm boolean DEFAULT false NOT NULL,
    hide_cw_search boolean DEFAULT true NOT NULL,
    hide_blocked_search boolean DEFAULT true NOT NULL,
    always_show_cw boolean DEFAULT false NOT NULL,
    compose_media_descriptions boolean DEFAULT false NOT NULL,
    reduce_motion boolean DEFAULT false NOT NULL,
    optimize_screen_reader boolean DEFAULT false NOT NULL,
    high_contrast_mode boolean DEFAULT false NOT NULL,
    video_autoplay boolean DEFAULT false NOT NULL,
    send_email_new_follower boolean DEFAULT false NOT NULL,
    send_email_new_follower_request boolean DEFAULT true NOT NULL,
    send_email_on_share boolean DEFAULT false NOT NULL,
    send_email_on_like boolean DEFAULT false NOT NULL,
    send_email_on_mention boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    show_profile_followers boolean DEFAULT true NOT NULL,
    show_profile_follower_count boolean DEFAULT true NOT NULL,
    show_profile_following boolean DEFAULT true NOT NULL,
    show_profile_following_count boolean DEFAULT true NOT NULL,
    compose_settings json,
    other json,
    show_atom boolean DEFAULT true NOT NULL
);


--
-- Name: user_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_settings_id_seq OWNED BY public.user_settings.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(191),
    username character varying(191),
    email character varying(191) NOT NULL,
    password character varying(191) NOT NULL,
    remember_token character varying(100),
    is_admin boolean DEFAULT false NOT NULL,
    email_verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    "2fa_enabled" boolean DEFAULT false NOT NULL,
    "2fa_secret" character varying(191),
    "2fa_backup_codes" json,
    "2fa_setup_at" timestamp(0) without time zone,
    status character varying(191),
    delete_after timestamp(0) without time zone,
    profile_id bigint,
    language character varying(191),
    has_interstitial boolean DEFAULT false NOT NULL,
    last_active_at timestamp(0) without time zone,
    guid character varying(191),
    domain character varying(191),
    register_source character varying(191) DEFAULT 'web'::character varying,
    app_register_token character varying(191),
    app_register_ip character varying(191),
    has_roles boolean DEFAULT false NOT NULL,
    parent_id integer,
    role_id smallint,
    expo_token character varying(191),
    notify_like boolean DEFAULT true NOT NULL,
    notify_follow boolean DEFAULT true NOT NULL,
    notify_mention boolean DEFAULT true NOT NULL,
    notify_comment boolean DEFAULT true NOT NULL,
    storage_used bigint DEFAULT '0'::bigint NOT NULL,
    storage_used_updated_at timestamp(0) without time zone,
    notify_enabled boolean DEFAULT false NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: websockets_statistics_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.websockets_statistics_entries (
    id integer NOT NULL,
    app_id character varying(191) NOT NULL,
    peak_connection_count integer NOT NULL,
    websocket_message_count integer NOT NULL,
    api_message_count integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: websockets_statistics_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.websockets_statistics_entries_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: websockets_statistics_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.websockets_statistics_entries_id_seq OWNED BY public.websockets_statistics_entries.id;


--
-- Name: account_interstitials id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_interstitials ALTER COLUMN id SET DEFAULT nextval('public.account_interstitials_id_seq'::regclass);


--
-- Name: account_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_logs ALTER COLUMN id SET DEFAULT nextval('public.account_logs_id_seq'::regclass);


--
-- Name: activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities ALTER COLUMN id SET DEFAULT nextval('public.activities_id_seq'::regclass);


--
-- Name: admin_invites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_invites ALTER COLUMN id SET DEFAULT nextval('public.admin_invites_id_seq'::regclass);


--
-- Name: admin_shadow_filters id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_shadow_filters ALTER COLUMN id SET DEFAULT nextval('public.admin_shadow_filters_id_seq'::regclass);


--
-- Name: app_registers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_registers ALTER COLUMN id SET DEFAULT nextval('public.app_registers_id_seq'::regclass);


--
-- Name: autospam_custom_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autospam_custom_tokens ALTER COLUMN id SET DEFAULT nextval('public.autospam_custom_tokens_id_seq'::regclass);


--
-- Name: avatars id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avatars ALTER COLUMN id SET DEFAULT nextval('public.avatars_id_seq'::regclass);


--
-- Name: bookmarks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks ALTER COLUMN id SET DEFAULT nextval('public.bookmarks_id_seq'::regclass);


--
-- Name: circle_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.circle_profiles ALTER COLUMN id SET DEFAULT nextval('public.circle_profiles_id_seq'::regclass);


--
-- Name: circles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.circles ALTER COLUMN id SET DEFAULT nextval('public.circles_id_seq'::regclass);


--
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- Name: config_cache id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.config_cache ALTER COLUMN id SET DEFAULT nextval('public.config_cache_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: conversations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations ALTER COLUMN id SET DEFAULT nextval('public.conversations_id_seq'::regclass);


--
-- Name: curated_register_activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_register_activities ALTER COLUMN id SET DEFAULT nextval('public.curated_register_activities_id_seq'::regclass);


--
-- Name: curated_register_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_register_templates ALTER COLUMN id SET DEFAULT nextval('public.curated_register_templates_id_seq'::regclass);


--
-- Name: curated_registers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_registers ALTER COLUMN id SET DEFAULT nextval('public.curated_registers_id_seq'::regclass);


--
-- Name: custom_emoji id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji ALTER COLUMN id SET DEFAULT nextval('public.custom_emoji_id_seq'::regclass);


--
-- Name: custom_emoji_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji_categories ALTER COLUMN id SET DEFAULT nextval('public.custom_emoji_categories_id_seq'::regclass);


--
-- Name: default_domain_blocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.default_domain_blocks ALTER COLUMN id SET DEFAULT nextval('public.default_domain_blocks_id_seq'::regclass);


--
-- Name: direct_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.direct_messages ALTER COLUMN id SET DEFAULT nextval('public.direct_messages_id_seq'::regclass);


--
-- Name: discover_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_categories ALTER COLUMN id SET DEFAULT nextval('public.discover_categories_id_seq'::regclass);


--
-- Name: discover_category_hashtags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_category_hashtags ALTER COLUMN id SET DEFAULT nextval('public.discover_category_hashtags_id_seq'::regclass);


--
-- Name: email_verifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_verifications ALTER COLUMN id SET DEFAULT nextval('public.email_verifications_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: follow_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow_requests ALTER COLUMN id SET DEFAULT nextval('public.follow_requests_id_seq'::regclass);


--
-- Name: followers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.followers ALTER COLUMN id SET DEFAULT nextval('public.followers_id_seq'::regclass);


--
-- Name: group_activity_graphs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_activity_graphs ALTER COLUMN id SET DEFAULT nextval('public.group_activity_graphs_id_seq'::regclass);


--
-- Name: group_blocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_blocks ALTER COLUMN id SET DEFAULT nextval('public.group_blocks_id_seq'::regclass);


--
-- Name: group_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_categories ALTER COLUMN id SET DEFAULT nextval('public.group_categories_id_seq'::regclass);


--
-- Name: group_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_comments ALTER COLUMN id SET DEFAULT nextval('public.group_comments_id_seq'::regclass);


--
-- Name: group_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_events ALTER COLUMN id SET DEFAULT nextval('public.group_events_id_seq'::regclass);


--
-- Name: group_hashtags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_hashtags ALTER COLUMN id SET DEFAULT nextval('public.group_hashtags_id_seq'::regclass);


--
-- Name: group_interactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_interactions ALTER COLUMN id SET DEFAULT nextval('public.group_interactions_id_seq'::regclass);


--
-- Name: group_invitations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_invitations ALTER COLUMN id SET DEFAULT nextval('public.group_invitations_id_seq'::regclass);


--
-- Name: group_likes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_likes ALTER COLUMN id SET DEFAULT nextval('public.group_likes_id_seq'::regclass);


--
-- Name: group_limits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_limits ALTER COLUMN id SET DEFAULT nextval('public.group_limits_id_seq'::regclass);


--
-- Name: group_media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_media ALTER COLUMN id SET DEFAULT nextval('public.group_media_id_seq'::regclass);


--
-- Name: group_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_members ALTER COLUMN id SET DEFAULT nextval('public.group_members_id_seq'::regclass);


--
-- Name: group_post_hashtags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags ALTER COLUMN id SET DEFAULT nextval('public.group_post_hashtags_id_seq'::regclass);


--
-- Name: group_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_reports ALTER COLUMN id SET DEFAULT nextval('public.group_reports_id_seq'::regclass);


--
-- Name: group_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_roles ALTER COLUMN id SET DEFAULT nextval('public.group_roles_id_seq'::regclass);


--
-- Name: group_stores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_stores ALTER COLUMN id SET DEFAULT nextval('public.group_stores_id_seq'::regclass);


--
-- Name: hashtag_follows id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_follows ALTER COLUMN id SET DEFAULT nextval('public.hashtag_follows_id_seq'::regclass);


--
-- Name: hashtag_related id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_related ALTER COLUMN id SET DEFAULT nextval('public.hashtag_related_id_seq'::regclass);


--
-- Name: hashtags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtags ALTER COLUMN id SET DEFAULT nextval('public.hashtags_id_seq'::regclass);


--
-- Name: import_datas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_datas ALTER COLUMN id SET DEFAULT nextval('public.import_datas_id_seq'::regclass);


--
-- Name: import_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs ALTER COLUMN id SET DEFAULT nextval('public.import_jobs_id_seq'::regclass);


--
-- Name: import_posts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_posts ALTER COLUMN id SET DEFAULT nextval('public.import_posts_id_seq'::regclass);


--
-- Name: instance_actors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instance_actors ALTER COLUMN id SET DEFAULT nextval('public.instance_actors_id_seq'::regclass);


--
-- Name: instances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instances ALTER COLUMN id SET DEFAULT nextval('public.instances_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: likes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.likes ALTER COLUMN id SET DEFAULT nextval('public.likes_id_seq'::regclass);


--
-- Name: live_streams id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.live_streams ALTER COLUMN id SET DEFAULT nextval('public.live_streams_id_seq'::regclass);


--
-- Name: login_links id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_links ALTER COLUMN id SET DEFAULT nextval('public.login_links_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: media_blocklists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_blocklists ALTER COLUMN id SET DEFAULT nextval('public.media_blocklists_id_seq'::regclass);


--
-- Name: media_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_tags ALTER COLUMN id SET DEFAULT nextval('public.media_tags_id_seq'::regclass);


--
-- Name: mentions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mentions ALTER COLUMN id SET DEFAULT nextval('public.mentions_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: mod_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mod_logs ALTER COLUMN id SET DEFAULT nextval('public.mod_logs_id_seq'::regclass);


--
-- Name: moderated_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderated_profiles ALTER COLUMN id SET DEFAULT nextval('public.moderated_profiles_id_seq'::regclass);


--
-- Name: newsroom id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.newsroom ALTER COLUMN id SET DEFAULT nextval('public.newsroom_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: oauth_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_clients ALTER COLUMN id SET DEFAULT nextval('public.oauth_clients_id_seq'::regclass);


--
-- Name: oauth_personal_access_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_personal_access_clients ALTER COLUMN id SET DEFAULT nextval('public.oauth_personal_access_clients_id_seq'::regclass);


--
-- Name: pages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages ALTER COLUMN id SET DEFAULT nextval('public.pages_id_seq'::regclass);


--
-- Name: parental_controls id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.parental_controls ALTER COLUMN id SET DEFAULT nextval('public.parental_controls_id_seq'::regclass);


--
-- Name: places id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.places ALTER COLUMN id SET DEFAULT nextval('public.places_id_seq'::regclass);


--
-- Name: poll_votes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.poll_votes ALTER COLUMN id SET DEFAULT nextval('public.poll_votes_id_seq'::regclass);


--
-- Name: portfolios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolios ALTER COLUMN id SET DEFAULT nextval('public.portfolios_id_seq'::regclass);


--
-- Name: profile_aliases id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_aliases ALTER COLUMN id SET DEFAULT nextval('public.profile_aliases_id_seq'::regclass);


--
-- Name: profile_migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_migrations ALTER COLUMN id SET DEFAULT nextval('public.profile_migrations_id_seq'::regclass);


--
-- Name: profile_sponsors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_sponsors ALTER COLUMN id SET DEFAULT nextval('public.profile_sponsors_id_seq'::regclass);


--
-- Name: profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profiles ALTER COLUMN id SET DEFAULT nextval('public.profiles_id_seq'::regclass);


--
-- Name: pulse_aggregates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_aggregates ALTER COLUMN id SET DEFAULT nextval('public.pulse_aggregates_id_seq'::regclass);


--
-- Name: pulse_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_entries ALTER COLUMN id SET DEFAULT nextval('public.pulse_entries_id_seq'::regclass);


--
-- Name: pulse_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_values ALTER COLUMN id SET DEFAULT nextval('public.pulse_values_id_seq'::regclass);


--
-- Name: push_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.push_subscriptions_id_seq'::regclass);


--
-- Name: remote_auth_instances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auth_instances ALTER COLUMN id SET DEFAULT nextval('public.remote_auth_instances_id_seq'::regclass);


--
-- Name: remote_auths id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auths ALTER COLUMN id SET DEFAULT nextval('public.remote_auths_id_seq'::regclass);


--
-- Name: remote_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_reports ALTER COLUMN id SET DEFAULT nextval('public.remote_reports_id_seq'::regclass);


--
-- Name: report_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_comments ALTER COLUMN id SET DEFAULT nextval('public.report_comments_id_seq'::regclass);


--
-- Name: report_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_logs ALTER COLUMN id SET DEFAULT nextval('public.report_logs_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: status_archiveds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_archiveds ALTER COLUMN id SET DEFAULT nextval('public.status_archiveds_id_seq'::regclass);


--
-- Name: status_edits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_edits ALTER COLUMN id SET DEFAULT nextval('public.status_edits_id_seq'::regclass);


--
-- Name: status_hashtags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_hashtags ALTER COLUMN id SET DEFAULT nextval('public.status_hashtags_id_seq'::regclass);


--
-- Name: status_views id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_views ALTER COLUMN id SET DEFAULT nextval('public.status_views_id_seq'::regclass);


--
-- Name: stories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories ALTER COLUMN id SET DEFAULT nextval('public.stories_id_seq'::regclass);


--
-- Name: story_views id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.story_views ALTER COLUMN id SET DEFAULT nextval('public.story_views_id_seq'::regclass);


--
-- Name: telescope_entries sequence; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries ALTER COLUMN sequence SET DEFAULT nextval('public.telescope_entries_sequence_seq'::regclass);


--
-- Name: uikit id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uikit ALTER COLUMN id SET DEFAULT nextval('public.uikit_id_seq'::regclass);


--
-- Name: user_app_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app_settings ALTER COLUMN id SET DEFAULT nextval('public.user_app_settings_id_seq'::regclass);


--
-- Name: user_devices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_devices ALTER COLUMN id SET DEFAULT nextval('public.user_devices_id_seq'::regclass);


--
-- Name: user_domain_blocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_domain_blocks ALTER COLUMN id SET DEFAULT nextval('public.user_domain_blocks_id_seq'::regclass);


--
-- Name: user_email_forgots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_email_forgots ALTER COLUMN id SET DEFAULT nextval('public.user_email_forgots_id_seq'::regclass);


--
-- Name: user_filters id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_filters ALTER COLUMN id SET DEFAULT nextval('public.user_filters_id_seq'::regclass);


--
-- Name: user_invites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invites ALTER COLUMN id SET DEFAULT nextval('public.user_invites_id_seq'::regclass);


--
-- Name: user_pronouns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_pronouns ALTER COLUMN id SET DEFAULT nextval('public.user_pronouns_id_seq'::regclass);


--
-- Name: user_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles ALTER COLUMN id SET DEFAULT nextval('public.user_roles_id_seq'::regclass);


--
-- Name: user_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings ALTER COLUMN id SET DEFAULT nextval('public.user_settings_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: websockets_statistics_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.websockets_statistics_entries ALTER COLUMN id SET DEFAULT nextval('public.websockets_statistics_entries_id_seq'::regclass);


--
-- Name: account_interstitials account_interstitials_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_interstitials
    ADD CONSTRAINT account_interstitials_pkey PRIMARY KEY (id);


--
-- Name: account_interstitials account_interstitials_thread_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_interstitials
    ADD CONSTRAINT account_interstitials_thread_id_unique UNIQUE (thread_id);


--
-- Name: account_logs account_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_logs
    ADD CONSTRAINT account_logs_pkey PRIMARY KEY (id);


--
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_pkey PRIMARY KEY (id);


--
-- Name: admin_invites admin_invites_invite_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_invites
    ADD CONSTRAINT admin_invites_invite_code_unique UNIQUE (invite_code);


--
-- Name: admin_invites admin_invites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_invites
    ADD CONSTRAINT admin_invites_pkey PRIMARY KEY (id);


--
-- Name: admin_shadow_filters admin_shadow_filters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_shadow_filters
    ADD CONSTRAINT admin_shadow_filters_pkey PRIMARY KEY (id);


--
-- Name: app_registers app_registers_email_verify_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_registers
    ADD CONSTRAINT app_registers_email_verify_code_unique UNIQUE (email, verify_code);


--
-- Name: app_registers app_registers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.app_registers
    ADD CONSTRAINT app_registers_pkey PRIMARY KEY (id);


--
-- Name: autospam_custom_tokens autospam_custom_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autospam_custom_tokens
    ADD CONSTRAINT autospam_custom_tokens_pkey PRIMARY KEY (id);


--
-- Name: autospam_custom_tokens autospam_custom_tokens_token_category_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autospam_custom_tokens
    ADD CONSTRAINT autospam_custom_tokens_token_category_unique UNIQUE (token, category);


--
-- Name: avatars avatars_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avatars
    ADD CONSTRAINT avatars_pkey PRIMARY KEY (id);


--
-- Name: avatars avatars_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avatars
    ADD CONSTRAINT avatars_profile_id_unique UNIQUE (profile_id);


--
-- Name: bookmarks bookmarks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks
    ADD CONSTRAINT bookmarks_pkey PRIMARY KEY (id);


--
-- Name: bookmarks bookmarks_status_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks
    ADD CONSTRAINT bookmarks_status_id_profile_id_unique UNIQUE (status_id, profile_id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: circle_profiles circle_profiles_circle_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.circle_profiles
    ADD CONSTRAINT circle_profiles_circle_id_profile_id_unique UNIQUE (circle_id, profile_id);


--
-- Name: circle_profiles circle_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.circle_profiles
    ADD CONSTRAINT circle_profiles_pkey PRIMARY KEY (id);


--
-- Name: circles circles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.circles
    ADD CONSTRAINT circles_pkey PRIMARY KEY (id);


--
-- Name: collection_items collection_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_items
    ADD CONSTRAINT collection_items_pkey PRIMARY KEY (id);


--
-- Name: collections collections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT collections_pkey PRIMARY KEY (id);


--
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: config_cache config_cache_k_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.config_cache
    ADD CONSTRAINT config_cache_k_unique UNIQUE (k);


--
-- Name: config_cache config_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.config_cache
    ADD CONSTRAINT config_cache_pkey PRIMARY KEY (id);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: conversations conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_pkey PRIMARY KEY (id);


--
-- Name: conversations conversations_to_id_from_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_to_id_from_id_unique UNIQUE (to_id, from_id);


--
-- Name: curated_register_activities curated_register_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_register_activities
    ADD CONSTRAINT curated_register_activities_pkey PRIMARY KEY (id);


--
-- Name: curated_register_templates curated_register_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_register_templates
    ADD CONSTRAINT curated_register_templates_pkey PRIMARY KEY (id);


--
-- Name: curated_registers curated_registers_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_registers
    ADD CONSTRAINT curated_registers_email_unique UNIQUE (email);


--
-- Name: curated_registers curated_registers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_registers
    ADD CONSTRAINT curated_registers_pkey PRIMARY KEY (id);


--
-- Name: curated_registers curated_registers_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curated_registers
    ADD CONSTRAINT curated_registers_username_unique UNIQUE (username);


--
-- Name: custom_emoji_categories custom_emoji_categories_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji_categories
    ADD CONSTRAINT custom_emoji_categories_name_unique UNIQUE (name);


--
-- Name: custom_emoji_categories custom_emoji_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji_categories
    ADD CONSTRAINT custom_emoji_categories_pkey PRIMARY KEY (id);


--
-- Name: custom_emoji custom_emoji_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji
    ADD CONSTRAINT custom_emoji_pkey PRIMARY KEY (id);


--
-- Name: custom_emoji custom_emoji_shortcode_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_emoji
    ADD CONSTRAINT custom_emoji_shortcode_domain_unique UNIQUE (shortcode, domain);


--
-- Name: default_domain_blocks default_domain_blocks_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.default_domain_blocks
    ADD CONSTRAINT default_domain_blocks_domain_unique UNIQUE (domain);


--
-- Name: default_domain_blocks default_domain_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.default_domain_blocks
    ADD CONSTRAINT default_domain_blocks_pkey PRIMARY KEY (id);


--
-- Name: direct_messages direct_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.direct_messages
    ADD CONSTRAINT direct_messages_pkey PRIMARY KEY (id);


--
-- Name: direct_messages direct_messages_to_id_from_id_status_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.direct_messages
    ADD CONSTRAINT direct_messages_to_id_from_id_status_id_unique UNIQUE (to_id, from_id, status_id);


--
-- Name: discover_category_hashtags disc_hashtag_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_category_hashtags
    ADD CONSTRAINT disc_hashtag_unique UNIQUE (discover_category_id, hashtag_id);


--
-- Name: discover_categories discover_categories_media_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_categories
    ADD CONSTRAINT discover_categories_media_id_unique UNIQUE (media_id);


--
-- Name: discover_categories discover_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_categories
    ADD CONSTRAINT discover_categories_pkey PRIMARY KEY (id);


--
-- Name: discover_categories discover_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_categories
    ADD CONSTRAINT discover_categories_slug_unique UNIQUE (slug);


--
-- Name: discover_category_hashtags discover_category_hashtags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discover_category_hashtags
    ADD CONSTRAINT discover_category_hashtags_pkey PRIMARY KEY (id);


--
-- Name: email_verifications email_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_verifications
    ADD CONSTRAINT email_verifications_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: user_filters filter_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_filters
    ADD CONSTRAINT filter_unique UNIQUE (user_id, filterable_id, filterable_type, filter_type);


--
-- Name: follow_requests follow_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow_requests
    ADD CONSTRAINT follow_requests_pkey PRIMARY KEY (id);


--
-- Name: followers followers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.followers
    ADD CONSTRAINT followers_pkey PRIMARY KEY (id);


--
-- Name: followers followers_profile_id_following_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.followers
    ADD CONSTRAINT followers_profile_id_following_id_unique UNIQUE (profile_id, following_id);


--
-- Name: group_activity_graphs group_activity_graphs_id_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_activity_graphs
    ADD CONSTRAINT group_activity_graphs_id_url_unique UNIQUE (id_url);


--
-- Name: group_activity_graphs group_activity_graphs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_activity_graphs
    ADD CONSTRAINT group_activity_graphs_pkey PRIMARY KEY (id);


--
-- Name: group_blocks group_blocks_group_id_profile_id_instance_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_blocks
    ADD CONSTRAINT group_blocks_group_id_profile_id_instance_id_unique UNIQUE (group_id, profile_id, instance_id);


--
-- Name: group_blocks group_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_blocks
    ADD CONSTRAINT group_blocks_pkey PRIMARY KEY (id);


--
-- Name: group_categories group_categories_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_categories
    ADD CONSTRAINT group_categories_name_unique UNIQUE (name);


--
-- Name: group_categories group_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_categories
    ADD CONSTRAINT group_categories_pkey PRIMARY KEY (id);


--
-- Name: group_categories group_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_categories
    ADD CONSTRAINT group_categories_slug_unique UNIQUE (slug);


--
-- Name: group_comments group_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_comments
    ADD CONSTRAINT group_comments_pkey PRIMARY KEY (id);


--
-- Name: group_comments group_comments_remote_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_comments
    ADD CONSTRAINT group_comments_remote_url_unique UNIQUE (remote_url);


--
-- Name: group_events group_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_events
    ADD CONSTRAINT group_events_pkey PRIMARY KEY (id);


--
-- Name: group_hashtags group_hashtags_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_hashtags
    ADD CONSTRAINT group_hashtags_name_unique UNIQUE (name);


--
-- Name: group_hashtags group_hashtags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_hashtags
    ADD CONSTRAINT group_hashtags_pkey PRIMARY KEY (id);


--
-- Name: group_interactions group_interactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_interactions
    ADD CONSTRAINT group_interactions_pkey PRIMARY KEY (id);


--
-- Name: group_invitations group_invitations_group_id_to_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_invitations
    ADD CONSTRAINT group_invitations_group_id_to_profile_id_unique UNIQUE (group_id, to_profile_id);


--
-- Name: group_invitations group_invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_invitations
    ADD CONSTRAINT group_invitations_pkey PRIMARY KEY (id);


--
-- Name: group_likes group_likes_gpsc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_likes
    ADD CONSTRAINT group_likes_gpsc_unique UNIQUE (group_id, profile_id, status_id, comment_id);


--
-- Name: group_likes group_likes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_likes
    ADD CONSTRAINT group_likes_pkey PRIMARY KEY (id);


--
-- Name: group_limits group_limits_group_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_limits
    ADD CONSTRAINT group_limits_group_id_profile_id_unique UNIQUE (group_id, profile_id);


--
-- Name: group_limits group_limits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_limits
    ADD CONSTRAINT group_limits_pkey PRIMARY KEY (id);


--
-- Name: group_media group_media_media_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_media
    ADD CONSTRAINT group_media_media_path_unique UNIQUE (media_path);


--
-- Name: group_media group_media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_media
    ADD CONSTRAINT group_media_pkey PRIMARY KEY (id);


--
-- Name: group_members group_members_group_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_members
    ADD CONSTRAINT group_members_group_id_profile_id_unique UNIQUE (group_id, profile_id);


--
-- Name: group_members group_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_members
    ADD CONSTRAINT group_members_pkey PRIMARY KEY (id);


--
-- Name: group_post_hashtags group_post_hashtags_gda_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_gda_unique UNIQUE (hashtag_id, group_id, profile_id, status_id);


--
-- Name: group_post_hashtags group_post_hashtags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_pkey PRIMARY KEY (id);


--
-- Name: group_posts group_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_posts
    ADD CONSTRAINT group_posts_pkey PRIMARY KEY (id);


--
-- Name: group_posts group_posts_remote_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_posts
    ADD CONSTRAINT group_posts_remote_url_unique UNIQUE (remote_url);


--
-- Name: group_reports group_reports_group_id_profile_id_item_type_item_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_reports
    ADD CONSTRAINT group_reports_group_id_profile_id_item_type_item_id_unique UNIQUE (group_id, profile_id, item_type, item_id);


--
-- Name: group_reports group_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_reports
    ADD CONSTRAINT group_reports_pkey PRIMARY KEY (id);


--
-- Name: group_roles group_roles_group_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_roles
    ADD CONSTRAINT group_roles_group_id_slug_unique UNIQUE (group_id, slug);


--
-- Name: group_roles group_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_roles
    ADD CONSTRAINT group_roles_pkey PRIMARY KEY (id);


--
-- Name: group_stores group_stores_group_id_store_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_stores
    ADD CONSTRAINT group_stores_group_id_store_key_unique UNIQUE (group_id, store_key);


--
-- Name: group_stores group_stores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_stores
    ADD CONSTRAINT group_stores_pkey PRIMARY KEY (id);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: hashtag_follows hashtag_follows_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_follows
    ADD CONSTRAINT hashtag_follows_pkey PRIMARY KEY (id);


--
-- Name: hashtag_follows hashtag_follows_user_id_profile_id_hashtag_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_follows
    ADD CONSTRAINT hashtag_follows_user_id_profile_id_hashtag_id_unique UNIQUE (user_id, profile_id, hashtag_id);


--
-- Name: hashtag_related hashtag_related_hashtag_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_related
    ADD CONSTRAINT hashtag_related_hashtag_id_unique UNIQUE (hashtag_id);


--
-- Name: hashtag_related hashtag_related_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtag_related
    ADD CONSTRAINT hashtag_related_pkey PRIMARY KEY (id);


--
-- Name: hashtags hashtags_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtags
    ADD CONSTRAINT hashtags_name_unique UNIQUE (name);


--
-- Name: hashtags hashtags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtags
    ADD CONSTRAINT hashtags_pkey PRIMARY KEY (id);


--
-- Name: hashtags hashtags_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hashtags
    ADD CONSTRAINT hashtags_slug_unique UNIQUE (slug);


--
-- Name: import_datas import_datas_job_id_original_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_datas
    ADD CONSTRAINT import_datas_job_id_original_name_unique UNIQUE (job_id, original_name);


--
-- Name: import_datas import_datas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_datas
    ADD CONSTRAINT import_datas_pkey PRIMARY KEY (id);


--
-- Name: import_jobs import_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_pkey PRIMARY KEY (id);


--
-- Name: import_posts import_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_posts
    ADD CONSTRAINT import_posts_pkey PRIMARY KEY (id);


--
-- Name: import_posts import_posts_status_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_posts
    ADD CONSTRAINT import_posts_status_id_unique UNIQUE (status_id);


--
-- Name: import_posts import_posts_uid_phash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_posts
    ADD CONSTRAINT import_posts_uid_phash_unique UNIQUE (user_id, creation_year, creation_month, creation_day, creation_id);


--
-- Name: import_posts import_posts_user_id_post_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_posts
    ADD CONSTRAINT import_posts_user_id_post_hash_unique UNIQUE (user_id, post_hash);


--
-- Name: instance_actors instance_actors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instance_actors
    ADD CONSTRAINT instance_actors_pkey PRIMARY KEY (id);


--
-- Name: instances instances_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instances
    ADD CONSTRAINT instances_domain_unique UNIQUE (domain);


--
-- Name: instances instances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instances
    ADD CONSTRAINT instances_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: likes likes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.likes
    ADD CONSTRAINT likes_pkey PRIMARY KEY (id);


--
-- Name: likes likes_profile_id_status_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.likes
    ADD CONSTRAINT likes_profile_id_status_id_unique UNIQUE (profile_id, status_id);


--
-- Name: live_streams live_streams_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.live_streams
    ADD CONSTRAINT live_streams_pkey PRIMARY KEY (id);


--
-- Name: live_streams live_streams_stream_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.live_streams
    ADD CONSTRAINT live_streams_stream_id_unique UNIQUE (stream_id);


--
-- Name: login_links login_links_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_links
    ADD CONSTRAINT login_links_pkey PRIMARY KEY (id);


--
-- Name: media_blocklists media_blocklists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_blocklists
    ADD CONSTRAINT media_blocklists_pkey PRIMARY KEY (id);


--
-- Name: media_blocklists media_blocklists_sha256_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_blocklists
    ADD CONSTRAINT media_blocklists_sha256_unique UNIQUE (sha256);


--
-- Name: media_blocklists media_blocklists_sha512_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_blocklists
    ADD CONSTRAINT media_blocklists_sha512_unique UNIQUE (sha512);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_status_id_media_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_status_id_media_path_unique UNIQUE (status_id, media_path);


--
-- Name: media_tags media_tags_media_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_tags
    ADD CONSTRAINT media_tags_media_id_profile_id_unique UNIQUE (media_id, profile_id);


--
-- Name: media_tags media_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media_tags
    ADD CONSTRAINT media_tags_pkey PRIMARY KEY (id);


--
-- Name: mentions mentions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mentions
    ADD CONSTRAINT mentions_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: mod_logs mod_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mod_logs
    ADD CONSTRAINT mod_logs_pkey PRIMARY KEY (id);


--
-- Name: moderated_profiles moderated_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderated_profiles
    ADD CONSTRAINT moderated_profiles_pkey PRIMARY KEY (id);


--
-- Name: moderated_profiles moderated_profiles_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderated_profiles
    ADD CONSTRAINT moderated_profiles_profile_id_unique UNIQUE (profile_id);


--
-- Name: moderated_profiles moderated_profiles_profile_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderated_profiles
    ADD CONSTRAINT moderated_profiles_profile_url_unique UNIQUE (profile_url);


--
-- Name: newsroom newsroom_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.newsroom
    ADD CONSTRAINT newsroom_pkey PRIMARY KEY (id);


--
-- Name: newsroom newsroom_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.newsroom
    ADD CONSTRAINT newsroom_slug_unique UNIQUE (slug);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: oauth_access_tokens oauth_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_access_tokens
    ADD CONSTRAINT oauth_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: oauth_auth_codes oauth_auth_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_auth_codes
    ADD CONSTRAINT oauth_auth_codes_pkey PRIMARY KEY (id);


--
-- Name: oauth_clients oauth_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_clients
    ADD CONSTRAINT oauth_clients_pkey PRIMARY KEY (id);


--
-- Name: oauth_personal_access_clients oauth_personal_access_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_personal_access_clients
    ADD CONSTRAINT oauth_personal_access_clients_pkey PRIMARY KEY (id);


--
-- Name: oauth_refresh_tokens oauth_refresh_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oauth_refresh_tokens
    ADD CONSTRAINT oauth_refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: pages pages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- Name: pages pages_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_slug_unique UNIQUE (slug);


--
-- Name: parental_controls parental_controls_child_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.parental_controls
    ADD CONSTRAINT parental_controls_child_id_unique UNIQUE (child_id);


--
-- Name: parental_controls parental_controls_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.parental_controls
    ADD CONSTRAINT parental_controls_email_unique UNIQUE (email);


--
-- Name: parental_controls parental_controls_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.parental_controls
    ADD CONSTRAINT parental_controls_pkey PRIMARY KEY (id);


--
-- Name: places places_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_pkey PRIMARY KEY (id);


--
-- Name: places places_slug_country_lat_long_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_slug_country_lat_long_unique UNIQUE (slug, country, lat, long);


--
-- Name: poll_votes poll_votes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.poll_votes
    ADD CONSTRAINT poll_votes_pkey PRIMARY KEY (id);


--
-- Name: polls polls_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.polls
    ADD CONSTRAINT polls_pkey PRIMARY KEY (id);


--
-- Name: portfolios portfolios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolios
    ADD CONSTRAINT portfolios_pkey PRIMARY KEY (id);


--
-- Name: portfolios portfolios_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolios
    ADD CONSTRAINT portfolios_profile_id_unique UNIQUE (profile_id);


--
-- Name: portfolios portfolios_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolios
    ADD CONSTRAINT portfolios_user_id_unique UNIQUE (user_id);


--
-- Name: profile_aliases profile_aliases_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_aliases
    ADD CONSTRAINT profile_aliases_pkey PRIMARY KEY (id);


--
-- Name: profile_aliases profile_id_acct_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_aliases
    ADD CONSTRAINT profile_id_acct_unique UNIQUE (profile_id, acct);


--
-- Name: profile_migrations profile_migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_migrations
    ADD CONSTRAINT profile_migrations_pkey PRIMARY KEY (id);


--
-- Name: profile_sponsors profile_sponsors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_sponsors
    ADD CONSTRAINT profile_sponsors_pkey PRIMARY KEY (id);


--
-- Name: profile_sponsors profile_sponsors_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_sponsors
    ADD CONSTRAINT profile_sponsors_profile_id_unique UNIQUE (profile_id);


--
-- Name: profiles profiles_domain_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_domain_username_unique UNIQUE (domain, username);


--
-- Name: profiles profiles_key_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_key_id_unique UNIQUE (key_id);


--
-- Name: profiles profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_pkey PRIMARY KEY (id);


--
-- Name: profiles profiles_webfinger_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_webfinger_unique UNIQUE (webfinger);


--
-- Name: pulse_aggregates pulse_aggregates_bucket_period_type_aggregate_key_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_aggregates
    ADD CONSTRAINT pulse_aggregates_bucket_period_type_aggregate_key_hash_unique UNIQUE (bucket, period, type, aggregate, key_hash);


--
-- Name: pulse_aggregates pulse_aggregates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_aggregates
    ADD CONSTRAINT pulse_aggregates_pkey PRIMARY KEY (id);


--
-- Name: pulse_entries pulse_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_entries
    ADD CONSTRAINT pulse_entries_pkey PRIMARY KEY (id);


--
-- Name: pulse_values pulse_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_values
    ADD CONSTRAINT pulse_values_pkey PRIMARY KEY (id);


--
-- Name: pulse_values pulse_values_type_key_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pulse_values
    ADD CONSTRAINT pulse_values_type_key_hash_unique UNIQUE (type, key_hash);


--
-- Name: push_subscriptions push_subscriptions_endpoint_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_endpoint_unique UNIQUE (endpoint);


--
-- Name: push_subscriptions push_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: remote_auth_instances remote_auth_instances_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auth_instances
    ADD CONSTRAINT remote_auth_instances_domain_unique UNIQUE (domain);


--
-- Name: remote_auth_instances remote_auth_instances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auth_instances
    ADD CONSTRAINT remote_auth_instances_pkey PRIMARY KEY (id);


--
-- Name: remote_auths remote_auths_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auths
    ADD CONSTRAINT remote_auths_pkey PRIMARY KEY (id);


--
-- Name: remote_auths remote_auths_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auths
    ADD CONSTRAINT remote_auths_user_id_unique UNIQUE (user_id);


--
-- Name: remote_auths remote_auths_webfinger_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_auths
    ADD CONSTRAINT remote_auths_webfinger_unique UNIQUE (webfinger);


--
-- Name: remote_reports remote_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_reports
    ADD CONSTRAINT remote_reports_pkey PRIMARY KEY (id);


--
-- Name: report_comments report_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_comments
    ADD CONSTRAINT report_comments_pkey PRIMARY KEY (id);


--
-- Name: report_logs report_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT report_logs_pkey PRIMARY KEY (id);


--
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- Name: reports reports_user_id_object_type_object_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_user_id_object_type_object_id_unique UNIQUE (user_id, object_type, object_id);


--
-- Name: sessions sessions_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_id_unique UNIQUE (id);


--
-- Name: status_archiveds status_archiveds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_archiveds
    ADD CONSTRAINT status_archiveds_pkey PRIMARY KEY (id);


--
-- Name: status_edits status_edits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_edits
    ADD CONSTRAINT status_edits_pkey PRIMARY KEY (id);


--
-- Name: status_hashtags status_hashtags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_hashtags
    ADD CONSTRAINT status_hashtags_pkey PRIMARY KEY (id);


--
-- Name: status_hashtags status_hashtags_status_id_hashtag_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_hashtags
    ADD CONSTRAINT status_hashtags_status_id_hashtag_id_unique UNIQUE (status_id, hashtag_id);


--
-- Name: status_views status_views_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_views
    ADD CONSTRAINT status_views_pkey PRIMARY KEY (id);


--
-- Name: status_views status_views_status_id_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_views
    ADD CONSTRAINT status_views_status_id_profile_id_unique UNIQUE (status_id, profile_id);


--
-- Name: statuses statuses_object_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.statuses
    ADD CONSTRAINT statuses_object_url_unique UNIQUE (object_url);


--
-- Name: statuses statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.statuses
    ADD CONSTRAINT statuses_pkey PRIMARY KEY (id);


--
-- Name: statuses statuses_uri_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.statuses
    ADD CONSTRAINT statuses_uri_unique UNIQUE (uri);


--
-- Name: stories stories_media_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_media_url_unique UNIQUE (media_url);


--
-- Name: stories stories_object_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_object_id_unique UNIQUE (object_id);


--
-- Name: stories stories_object_uri_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_object_uri_unique UNIQUE (object_uri);


--
-- Name: stories stories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_pkey PRIMARY KEY (id);


--
-- Name: stories stories_profile_id_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_profile_id_path_unique UNIQUE (profile_id, path);


--
-- Name: stories stories_remote_url_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_remote_url_unique UNIQUE (remote_url);


--
-- Name: story_views story_views_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.story_views
    ADD CONSTRAINT story_views_pkey PRIMARY KEY (id);


--
-- Name: story_views story_views_profile_id_story_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.story_views
    ADD CONSTRAINT story_views_profile_id_story_id_unique UNIQUE (profile_id, story_id);


--
-- Name: telescope_entries telescope_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_pkey PRIMARY KEY (sequence);


--
-- Name: telescope_entries_tags telescope_entries_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_pkey PRIMARY KEY (entry_uuid, tag);


--
-- Name: telescope_entries telescope_entries_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_uuid_unique UNIQUE (uuid);


--
-- Name: telescope_monitoring telescope_monitoring_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_monitoring
    ADD CONSTRAINT telescope_monitoring_pkey PRIMARY KEY (tag);


--
-- Name: uikit uikit_k_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uikit
    ADD CONSTRAINT uikit_k_unique UNIQUE (k);


--
-- Name: uikit uikit_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uikit
    ADD CONSTRAINT uikit_pkey PRIMARY KEY (id);


--
-- Name: user_app_settings user_app_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app_settings
    ADD CONSTRAINT user_app_settings_pkey PRIMARY KEY (id);


--
-- Name: user_app_settings user_app_settings_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app_settings
    ADD CONSTRAINT user_app_settings_profile_id_unique UNIQUE (profile_id);


--
-- Name: user_app_settings user_app_settings_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app_settings
    ADD CONSTRAINT user_app_settings_user_id_unique UNIQUE (user_id);


--
-- Name: user_devices user_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_devices
    ADD CONSTRAINT user_devices_pkey PRIMARY KEY (id);


--
-- Name: user_domain_blocks user_domain_blocks_by_id; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_domain_blocks
    ADD CONSTRAINT user_domain_blocks_by_id UNIQUE (profile_id, domain);


--
-- Name: user_domain_blocks user_domain_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_domain_blocks
    ADD CONSTRAINT user_domain_blocks_pkey PRIMARY KEY (id);


--
-- Name: user_email_forgots user_email_forgots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_email_forgots
    ADD CONSTRAINT user_email_forgots_pkey PRIMARY KEY (id);


--
-- Name: user_filters user_filters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_filters
    ADD CONSTRAINT user_filters_pkey PRIMARY KEY (id);


--
-- Name: user_invites user_invites_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invites
    ADD CONSTRAINT user_invites_email_unique UNIQUE (email);


--
-- Name: user_invites user_invites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invites
    ADD CONSTRAINT user_invites_pkey PRIMARY KEY (id);


--
-- Name: user_devices user_ip_agent_index; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_devices
    ADD CONSTRAINT user_ip_agent_index UNIQUE (user_id, ip, user_agent, fingerprint);


--
-- Name: user_pronouns user_pronouns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_pronouns
    ADD CONSTRAINT user_pronouns_pkey PRIMARY KEY (id);


--
-- Name: user_pronouns user_pronouns_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_pronouns
    ADD CONSTRAINT user_pronouns_profile_id_unique UNIQUE (profile_id);


--
-- Name: user_pronouns user_pronouns_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_pronouns
    ADD CONSTRAINT user_pronouns_user_id_unique UNIQUE (user_id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_profile_id_unique UNIQUE (profile_id);


--
-- Name: user_roles user_roles_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_unique UNIQUE (user_id);


--
-- Name: user_settings user_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings
    ADD CONSTRAINT user_settings_pkey PRIMARY KEY (id);


--
-- Name: user_settings user_settings_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_settings
    ADD CONSTRAINT user_settings_user_id_unique UNIQUE (user_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_guid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_guid_unique UNIQUE (guid);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_profile_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_profile_id_unique UNIQUE (profile_id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: websockets_statistics_entries websockets_statistics_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.websockets_statistics_entries
    ADD CONSTRAINT websockets_statistics_entries_pkey PRIMARY KEY (id);


--
-- Name: account_interstitials_appeal_handled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_appeal_handled_at_index ON public.account_interstitials USING btree (appeal_handled_at);


--
-- Name: account_interstitials_appeal_requested_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_appeal_requested_at_index ON public.account_interstitials USING btree (appeal_requested_at);


--
-- Name: account_interstitials_email_notify_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_email_notify_index ON public.account_interstitials USING btree (email_notify);


--
-- Name: account_interstitials_in_violation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_in_violation_index ON public.account_interstitials USING btree (in_violation);


--
-- Name: account_interstitials_is_spam_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_is_spam_index ON public.account_interstitials USING btree (is_spam);


--
-- Name: account_interstitials_read_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_read_at_index ON public.account_interstitials USING btree (read_at);


--
-- Name: account_interstitials_severity_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_severity_index_index ON public.account_interstitials USING btree (severity_index);


--
-- Name: account_interstitials_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_user_id_index ON public.account_interstitials USING btree (user_id);


--
-- Name: account_interstitials_violation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_interstitials_violation_id_index ON public.account_interstitials USING btree (violation_id);


--
-- Name: account_logs_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_logs_user_id_index ON public.account_logs USING btree (user_id);


--
-- Name: admin_shadow_filters_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_active_index ON public.admin_shadow_filters USING btree (active);


--
-- Name: admin_shadow_filters_hide_embeds_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_embeds_index ON public.admin_shadow_filters USING btree (hide_embeds);


--
-- Name: admin_shadow_filters_hide_from_public_feeds_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_from_public_feeds_index ON public.admin_shadow_filters USING btree (hide_from_public_feeds);


--
-- Name: admin_shadow_filters_hide_from_search_autocomplete_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_from_search_autocomplete_index ON public.admin_shadow_filters USING btree (hide_from_search_autocomplete);


--
-- Name: admin_shadow_filters_hide_from_search_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_from_search_index ON public.admin_shadow_filters USING btree (hide_from_search);


--
-- Name: admin_shadow_filters_hide_from_story_carousel_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_from_story_carousel_index ON public.admin_shadow_filters USING btree (hide_from_story_carousel);


--
-- Name: admin_shadow_filters_hide_from_tag_feeds_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_hide_from_tag_feeds_index ON public.admin_shadow_filters USING btree (hide_from_tag_feeds);


--
-- Name: admin_shadow_filters_ignore_hashtags_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_ignore_hashtags_index ON public.admin_shadow_filters USING btree (ignore_hashtags);


--
-- Name: admin_shadow_filters_ignore_links_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_ignore_links_index ON public.admin_shadow_filters USING btree (ignore_links);


--
-- Name: admin_shadow_filters_ignore_mentions_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_ignore_mentions_index ON public.admin_shadow_filters USING btree (ignore_mentions);


--
-- Name: admin_shadow_filters_ignore_reports_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_ignore_reports_index ON public.admin_shadow_filters USING btree (ignore_reports);


--
-- Name: admin_shadow_filters_is_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_is_local_index ON public.admin_shadow_filters USING btree (is_local);


--
-- Name: admin_shadow_filters_item_type_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_item_type_item_id_index ON public.admin_shadow_filters USING btree (item_type, item_id);


--
-- Name: admin_shadow_filters_prevent_ap_fanout_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_prevent_ap_fanout_index ON public.admin_shadow_filters USING btree (prevent_ap_fanout);


--
-- Name: admin_shadow_filters_prevent_new_dms_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_prevent_new_dms_index ON public.admin_shadow_filters USING btree (prevent_new_dms);


--
-- Name: admin_shadow_filters_requires_login_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_shadow_filters_requires_login_index ON public.admin_shadow_filters USING btree (requires_login);


--
-- Name: autospam_custom_tokens_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX autospam_custom_tokens_active_index ON public.autospam_custom_tokens USING btree (active);


--
-- Name: autospam_custom_tokens_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX autospam_custom_tokens_category_index ON public.autospam_custom_tokens USING btree (category);


--
-- Name: autospam_custom_tokens_is_spam_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX autospam_custom_tokens_is_spam_index ON public.autospam_custom_tokens USING btree (is_spam);


--
-- Name: autospam_custom_tokens_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX autospam_custom_tokens_token_index ON public.autospam_custom_tokens USING btree (token);


--
-- Name: autospam_custom_tokens_weight_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX autospam_custom_tokens_weight_index ON public.autospam_custom_tokens USING btree (weight);


--
-- Name: avatars_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX avatars_deleted_at_index ON public.avatars USING btree (deleted_at);


--
-- Name: avatars_is_remote_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX avatars_is_remote_index ON public.avatars USING btree (is_remote);


--
-- Name: avatars_remote_url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX avatars_remote_url_index ON public.avatars USING btree (remote_url);


--
-- Name: bookmarks_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookmarks_profile_id_index ON public.bookmarks USING btree (profile_id);


--
-- Name: bookmarks_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookmarks_status_id_index ON public.bookmarks USING btree (status_id);


--
-- Name: circle_profiles_circle_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX circle_profiles_circle_id_index ON public.circle_profiles USING btree (circle_id);


--
-- Name: circle_profiles_owner_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX circle_profiles_owner_id_index ON public.circle_profiles USING btree (owner_id);


--
-- Name: circle_profiles_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX circle_profiles_profile_id_index ON public.circle_profiles USING btree (profile_id);


--
-- Name: circles_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX circles_active_index ON public.circles USING btree (active);


--
-- Name: circles_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX circles_profile_id_index ON public.circles USING btree (profile_id);


--
-- Name: collection_items_collection_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collection_items_collection_id_index ON public.collection_items USING btree (collection_id);


--
-- Name: collection_items_object_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collection_items_object_id_index ON public.collection_items USING btree (object_id);


--
-- Name: collection_items_object_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collection_items_object_type_index ON public.collection_items USING btree (object_type);


--
-- Name: collections_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collections_visibility_index ON public.collections USING btree (visibility);


--
-- Name: contacts_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contacts_user_id_index ON public.contacts USING btree (user_id);


--
-- Name: conversations_from_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_from_id_index ON public.conversations USING btree (from_id);


--
-- Name: conversations_has_seen_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_has_seen_index ON public.conversations USING btree (has_seen);


--
-- Name: conversations_is_hidden_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_is_hidden_index ON public.conversations USING btree (is_hidden);


--
-- Name: conversations_to_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_to_id_index ON public.conversations USING btree (to_id);


--
-- Name: curated_register_activities_from_admin_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_activities_from_admin_index ON public.curated_register_activities USING btree (from_admin);


--
-- Name: curated_register_activities_from_user_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_activities_from_user_index ON public.curated_register_activities USING btree (from_user);


--
-- Name: curated_register_activities_register_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_activities_register_id_index ON public.curated_register_activities USING btree (register_id);


--
-- Name: curated_register_activities_reply_to_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_activities_reply_to_id_index ON public.curated_register_activities USING btree (reply_to_id);


--
-- Name: curated_register_activities_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_activities_type_index ON public.curated_register_activities USING btree (type);


--
-- Name: curated_register_templates_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_templates_is_active_index ON public.curated_register_templates USING btree (is_active);


--
-- Name: curated_register_templates_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_register_templates_order_index ON public.curated_register_templates USING btree ("order");


--
-- Name: curated_registers_invited_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_invited_by_index ON public.curated_registers USING btree (invited_by);


--
-- Name: curated_registers_is_approved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_is_approved_index ON public.curated_registers USING btree (is_approved);


--
-- Name: curated_registers_is_awaiting_more_info_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_is_awaiting_more_info_index ON public.curated_registers USING btree (is_awaiting_more_info);


--
-- Name: curated_registers_is_closed_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_is_closed_index ON public.curated_registers USING btree (is_closed);


--
-- Name: curated_registers_is_rejected_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_is_rejected_index ON public.curated_registers USING btree (is_rejected);


--
-- Name: curated_registers_user_has_responded_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX curated_registers_user_has_responded_index ON public.curated_registers USING btree (user_has_responded);


--
-- Name: custom_emoji_categories_disabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_emoji_categories_disabled_index ON public.custom_emoji_categories USING btree (disabled);


--
-- Name: custom_emoji_disabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_emoji_disabled_index ON public.custom_emoji USING btree (disabled);


--
-- Name: custom_emoji_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_emoji_domain_index ON public.custom_emoji USING btree (domain);


--
-- Name: custom_emoji_shortcode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_emoji_shortcode_index ON public.custom_emoji USING btree (shortcode);


--
-- Name: direct_messages_from_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_from_id_index ON public.direct_messages USING btree (from_id);


--
-- Name: direct_messages_group_message_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_group_message_index ON public.direct_messages USING btree (group_message);


--
-- Name: direct_messages_is_hidden_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_is_hidden_index ON public.direct_messages USING btree (is_hidden);


--
-- Name: direct_messages_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_status_id_index ON public.direct_messages USING btree (status_id);


--
-- Name: direct_messages_to_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_to_id_index ON public.direct_messages USING btree (to_id);


--
-- Name: direct_messages_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX direct_messages_type_index ON public.direct_messages USING btree (type);


--
-- Name: discover_categories_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX discover_categories_active_index ON public.discover_categories USING btree (active);


--
-- Name: discover_category_hashtags_discover_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX discover_category_hashtags_discover_category_id_index ON public.discover_category_hashtags USING btree (discover_category_id);


--
-- Name: discover_category_hashtags_hashtag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX discover_category_hashtags_hashtag_id_index ON public.discover_category_hashtags USING btree (hashtag_id);


--
-- Name: email_verifications_random_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_verifications_random_token_index ON public.email_verifications USING btree (random_token);


--
-- Name: email_verifications_user_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_verifications_user_token_index ON public.email_verifications USING btree (user_token);


--
-- Name: follow_requests_follower_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX follow_requests_follower_id_index ON public.follow_requests USING btree (follower_id);


--
-- Name: follow_requests_following_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX follow_requests_following_id_index ON public.follow_requests USING btree (following_id);


--
-- Name: followers_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_created_at_index ON public.followers USING btree (created_at);


--
-- Name: followers_following_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_following_id_index ON public.followers USING btree (following_id);


--
-- Name: followers_local_following_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_local_following_index ON public.followers USING btree (local_following);


--
-- Name: followers_local_profile_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_local_profile_index ON public.followers USING btree (local_profile);


--
-- Name: followers_notify_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_notify_index ON public.followers USING btree (notify);


--
-- Name: followers_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_profile_id_index ON public.followers USING btree (profile_id);


--
-- Name: followers_show_reblogs_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX followers_show_reblogs_index ON public.followers USING btree (show_reblogs);


--
-- Name: group_activity_graphs_actor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_activity_graphs_actor_id_index ON public.group_activity_graphs USING btree (actor_id);


--
-- Name: group_activity_graphs_instance_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_activity_graphs_instance_id_index ON public.group_activity_graphs USING btree (instance_id);


--
-- Name: group_activity_graphs_verb_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_activity_graphs_verb_index ON public.group_activity_graphs USING btree (verb);


--
-- Name: group_blocks_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_group_id_index ON public.group_blocks USING btree (group_id);


--
-- Name: group_blocks_instance_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_instance_id_index ON public.group_blocks USING btree (instance_id);


--
-- Name: group_blocks_is_user_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_is_user_index ON public.group_blocks USING btree (is_user);


--
-- Name: group_blocks_moderated_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_moderated_index ON public.group_blocks USING btree (moderated);


--
-- Name: group_blocks_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_name_index ON public.group_blocks USING btree (name);


--
-- Name: group_blocks_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_blocks_profile_id_index ON public.group_blocks USING btree (profile_id);


--
-- Name: group_categories_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_categories_active_index ON public.group_categories USING btree (active);


--
-- Name: group_comments_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_comments_group_id_index ON public.group_comments USING btree (group_id);


--
-- Name: group_comments_in_reply_to_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_comments_in_reply_to_id_index ON public.group_comments USING btree (in_reply_to_id);


--
-- Name: group_comments_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_comments_status_id_index ON public.group_comments USING btree (status_id);


--
-- Name: group_events_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_events_group_id_index ON public.group_events USING btree (group_id);


--
-- Name: group_events_open_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_events_open_index ON public.group_events USING btree (open);


--
-- Name: group_events_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_events_profile_id_index ON public.group_events USING btree (profile_id);


--
-- Name: group_events_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_events_type_index ON public.group_events USING btree (type);


--
-- Name: group_interactions_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_interactions_group_id_index ON public.group_interactions USING btree (group_id);


--
-- Name: group_interactions_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_interactions_item_id_index ON public.group_interactions USING btree (item_id);


--
-- Name: group_interactions_item_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_interactions_item_type_index ON public.group_interactions USING btree (item_type);


--
-- Name: group_interactions_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_interactions_profile_id_index ON public.group_interactions USING btree (profile_id);


--
-- Name: group_interactions_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_interactions_type_index ON public.group_interactions USING btree (type);


--
-- Name: group_invitations_from_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_invitations_from_local_index ON public.group_invitations USING btree (from_local);


--
-- Name: group_invitations_from_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_invitations_from_profile_id_index ON public.group_invitations USING btree (from_profile_id);


--
-- Name: group_invitations_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_invitations_group_id_index ON public.group_invitations USING btree (group_id);


--
-- Name: group_invitations_to_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_invitations_to_local_index ON public.group_invitations USING btree (to_local);


--
-- Name: group_invitations_to_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_invitations_to_profile_id_index ON public.group_invitations USING btree (to_profile_id);


--
-- Name: group_likes_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_likes_profile_id_index ON public.group_likes USING btree (profile_id);


--
-- Name: group_limits_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_limits_group_id_index ON public.group_limits USING btree (group_id);


--
-- Name: group_limits_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_limits_profile_id_index ON public.group_limits USING btree (profile_id);


--
-- Name: group_media_is_comment_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_media_is_comment_index ON public.group_media USING btree (is_comment);


--
-- Name: group_media_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_media_status_id_index ON public.group_media USING btree (status_id);


--
-- Name: group_members_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_group_id_index ON public.group_members USING btree (group_id);


--
-- Name: group_members_join_request_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_join_request_index ON public.group_members USING btree (join_request);


--
-- Name: group_members_local_group_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_local_group_index ON public.group_members USING btree (local_group);


--
-- Name: group_members_local_profile_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_local_profile_index ON public.group_members USING btree (local_profile);


--
-- Name: group_members_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_profile_id_index ON public.group_members USING btree (profile_id);


--
-- Name: group_members_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_members_role_index ON public.group_members USING btree (role);


--
-- Name: group_post_hashtags_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_post_hashtags_group_id_index ON public.group_post_hashtags USING btree (group_id);


--
-- Name: group_post_hashtags_hashtag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_post_hashtags_hashtag_id_index ON public.group_post_hashtags USING btree (hashtag_id);


--
-- Name: group_posts_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_posts_group_id_index ON public.group_posts USING btree (group_id);


--
-- Name: group_posts_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_posts_profile_id_index ON public.group_posts USING btree (profile_id);


--
-- Name: group_posts_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_posts_status_index ON public.group_posts USING btree (status);


--
-- Name: group_posts_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_posts_type_index ON public.group_posts USING btree (type);


--
-- Name: group_reports_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_group_id_index ON public.group_reports USING btree (group_id);


--
-- Name: group_reports_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_item_id_index ON public.group_reports USING btree (item_id);


--
-- Name: group_reports_item_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_item_type_index ON public.group_reports USING btree (item_type);


--
-- Name: group_reports_open_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_open_index ON public.group_reports USING btree (open);


--
-- Name: group_reports_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_profile_id_index ON public.group_reports USING btree (profile_id);


--
-- Name: group_reports_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_reports_type_index ON public.group_reports USING btree (type);


--
-- Name: group_roles_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_roles_group_id_index ON public.group_roles USING btree (group_id);


--
-- Name: group_stores_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_stores_group_id_index ON public.group_stores USING btree (group_id);


--
-- Name: group_stores_store_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX group_stores_store_key_index ON public.group_stores USING btree (store_key);


--
-- Name: groups_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_category_id_index ON public.groups USING btree (category_id);


--
-- Name: groups_discoverable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_discoverable_index ON public.groups USING btree (discoverable);


--
-- Name: groups_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_local_index ON public.groups USING btree (local);


--
-- Name: groups_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_profile_id_index ON public.groups USING btree (profile_id);


--
-- Name: groups_recommended_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_recommended_index ON public.groups USING btree (recommended);


--
-- Name: groups_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX groups_status_index ON public.groups USING btree (status);


--
-- Name: hashtag_follows_hashtag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_follows_hashtag_id_index ON public.hashtag_follows USING btree (hashtag_id);


--
-- Name: hashtag_follows_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_follows_profile_id_index ON public.hashtag_follows USING btree (profile_id);


--
-- Name: hashtag_follows_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_follows_user_id_index ON public.hashtag_follows USING btree (user_id);


--
-- Name: hashtag_related_agg_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_related_agg_score_index ON public.hashtag_related USING btree (agg_score);


--
-- Name: hashtag_related_last_calculated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_related_last_calculated_at_index ON public.hashtag_related USING btree (last_calculated_at);


--
-- Name: hashtag_related_last_moderated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_related_last_moderated_at_index ON public.hashtag_related USING btree (last_moderated_at);


--
-- Name: hashtag_related_skip_refresh_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtag_related_skip_refresh_index ON public.hashtag_related USING btree (skip_refresh);


--
-- Name: hashtags_can_search_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtags_can_search_index ON public.hashtags USING btree (can_search);


--
-- Name: hashtags_can_trend_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtags_can_trend_index ON public.hashtags USING btree (can_trend);


--
-- Name: hashtags_is_banned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtags_is_banned_index ON public.hashtags USING btree (is_banned);


--
-- Name: hashtags_is_nsfw_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hashtags_is_nsfw_index ON public.hashtags USING btree (is_nsfw);


--
-- Name: import_posts_filename_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_filename_index ON public.import_posts USING btree (filename);


--
-- Name: import_posts_post_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_post_hash_index ON public.import_posts USING btree (post_hash);


--
-- Name: import_posts_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_profile_id_index ON public.import_posts USING btree (profile_id);


--
-- Name: import_posts_service_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_service_index ON public.import_posts USING btree (service);


--
-- Name: import_posts_skip_missing_media_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_skip_missing_media_index ON public.import_posts USING btree (skip_missing_media);


--
-- Name: import_posts_uploaded_to_s3_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_uploaded_to_s3_index ON public.import_posts USING btree (uploaded_to_s3);


--
-- Name: import_posts_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_posts_user_id_index ON public.import_posts USING btree (user_id);


--
-- Name: instances_active_deliver_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_active_deliver_index ON public.instances USING btree (active_deliver);


--
-- Name: instances_auto_cw_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_auto_cw_index ON public.instances USING btree (auto_cw);


--
-- Name: instances_ban_subdomains_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_ban_subdomains_index ON public.instances USING btree (ban_subdomains);


--
-- Name: instances_banned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_banned_index ON public.instances USING btree (banned);


--
-- Name: instances_list_limitation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_list_limitation_index ON public.instances USING btree (list_limitation);


--
-- Name: instances_nodeinfo_last_fetched_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_nodeinfo_last_fetched_index ON public.instances USING btree (nodeinfo_last_fetched);


--
-- Name: instances_shared_inbox_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_shared_inbox_index ON public.instances USING btree (shared_inbox);


--
-- Name: instances_software_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_software_index ON public.instances USING btree (software);


--
-- Name: instances_unlisted_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instances_unlisted_index ON public.instances USING btree (unlisted);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: likes_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_created_at_index ON public.likes USING btree (created_at);


--
-- Name: likes_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_deleted_at_index ON public.likes USING btree (deleted_at);


--
-- Name: likes_is_comment_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_is_comment_index ON public.likes USING btree (is_comment);


--
-- Name: likes_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_profile_id_index ON public.likes USING btree (profile_id);


--
-- Name: likes_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_status_id_index ON public.likes USING btree (status_id);


--
-- Name: likes_status_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX likes_status_profile_id_index ON public.likes USING btree (status_profile_id);


--
-- Name: live_streams_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX live_streams_profile_id_index ON public.live_streams USING btree (profile_id);


--
-- Name: login_links_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_key_index ON public.login_links USING btree (key);


--
-- Name: login_links_resent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_resent_at_index ON public.login_links USING btree (resent_at);


--
-- Name: login_links_revoked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_revoked_at_index ON public.login_links USING btree (revoked_at);


--
-- Name: login_links_secret_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_secret_index ON public.login_links USING btree (secret);


--
-- Name: login_links_used_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_used_at_index ON public.login_links USING btree (used_at);


--
-- Name: login_links_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_links_user_id_index ON public.login_links USING btree (user_id);


--
-- Name: media_blocklists_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_blocklists_active_index ON public.media_blocklists USING btree (active);


--
-- Name: media_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_deleted_at_index ON public.media USING btree (deleted_at);


--
-- Name: media_license_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_license_index ON public.media USING btree (license);


--
-- Name: media_mime_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_mime_index ON public.media USING btree (mime);


--
-- Name: media_optimized_sha256_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_optimized_sha256_index ON public.media USING btree (optimized_sha256);


--
-- Name: media_original_sha256_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_original_sha256_index ON public.media USING btree (original_sha256);


--
-- Name: media_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_profile_id_index ON public.media USING btree (profile_id);


--
-- Name: media_remote_media_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_remote_media_index ON public.media USING btree (remote_media);


--
-- Name: media_replicated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_replicated_at_index ON public.media USING btree (replicated_at);


--
-- Name: media_skip_optimize_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_skip_optimize_index ON public.media USING btree (skip_optimize);


--
-- Name: media_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_status_id_index ON public.media USING btree (status_id);


--
-- Name: media_tags_is_public_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_tags_is_public_index ON public.media_tags USING btree (is_public);


--
-- Name: media_tags_media_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_tags_media_id_index ON public.media_tags USING btree (media_id);


--
-- Name: media_tags_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_tags_profile_id_index ON public.media_tags USING btree (profile_id);


--
-- Name: media_tags_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_tags_status_id_index ON public.media_tags USING btree (status_id);


--
-- Name: media_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_user_id_index ON public.media USING btree (user_id);


--
-- Name: media_version_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_version_index ON public.media USING btree (version);


--
-- Name: mentions_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mentions_deleted_at_index ON public.mentions USING btree (deleted_at);


--
-- Name: mentions_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mentions_profile_id_index ON public.mentions USING btree (profile_id);


--
-- Name: mentions_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mentions_status_id_index ON public.mentions USING btree (status_id);


--
-- Name: mod_logs_object_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mod_logs_object_id_index ON public.mod_logs USING btree (object_id);


--
-- Name: mod_logs_object_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mod_logs_object_type_index ON public.mod_logs USING btree (object_type);


--
-- Name: mod_logs_object_uid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mod_logs_object_uid_index ON public.mod_logs USING btree (object_uid);


--
-- Name: mod_logs_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mod_logs_user_id_index ON public.mod_logs USING btree (user_id);


--
-- Name: notifications_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_action_index ON public.notifications USING btree (action);


--
-- Name: notifications_actor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_actor_id_index ON public.notifications USING btree (actor_id);


--
-- Name: notifications_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_created_at_index ON public.notifications USING btree (created_at);


--
-- Name: notifications_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_deleted_at_index ON public.notifications USING btree (deleted_at);


--
-- Name: notifications_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_item_id_index ON public.notifications USING btree (item_id);


--
-- Name: notifications_item_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_item_type_index ON public.notifications USING btree (item_type);


--
-- Name: notifications_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_profile_id_index ON public.notifications USING btree (profile_id);


--
-- Name: oauth_access_tokens_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_access_tokens_user_id_index ON public.oauth_access_tokens USING btree (user_id);


--
-- Name: oauth_auth_codes_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_auth_codes_user_id_index ON public.oauth_auth_codes USING btree (user_id);


--
-- Name: oauth_clients_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_clients_user_id_index ON public.oauth_clients USING btree (user_id);


--
-- Name: oauth_refresh_tokens_access_token_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX oauth_refresh_tokens_access_token_id_index ON public.oauth_refresh_tokens USING btree (access_token_id);


--
-- Name: pages_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pages_active_index ON public.pages USING btree (active);


--
-- Name: pages_cached_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pages_cached_index ON public.pages USING btree (cached);


--
-- Name: pages_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pages_category_id_index ON public.pages USING btree (category_id);


--
-- Name: pages_root_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pages_root_index ON public.pages USING btree (root);


--
-- Name: pages_template_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pages_template_index ON public.pages USING btree (template);


--
-- Name: parental_controls_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX parental_controls_parent_id_index ON public.parental_controls USING btree (parent_id);


--
-- Name: password_resets_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX password_resets_email_index ON public.password_resets USING btree (email);


--
-- Name: places_country_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_country_index ON public.places USING btree (country);


--
-- Name: places_last_checked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_last_checked_at_index ON public.places USING btree (last_checked_at);


--
-- Name: places_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_name_index ON public.places USING btree (name);


--
-- Name: places_score_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_score_index ON public.places USING btree (score);


--
-- Name: places_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_slug_index ON public.places USING btree (slug);


--
-- Name: places_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX places_state_index ON public.places USING btree (state);


--
-- Name: poll_votes_choice_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX poll_votes_choice_index ON public.poll_votes USING btree (choice);


--
-- Name: poll_votes_poll_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX poll_votes_poll_id_index ON public.poll_votes USING btree (poll_id);


--
-- Name: poll_votes_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX poll_votes_profile_id_index ON public.poll_votes USING btree (profile_id);


--
-- Name: poll_votes_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX poll_votes_status_id_index ON public.poll_votes USING btree (status_id);


--
-- Name: poll_votes_story_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX poll_votes_story_id_index ON public.poll_votes USING btree (story_id);


--
-- Name: polls_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX polls_group_id_index ON public.polls USING btree (group_id);


--
-- Name: polls_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX polls_profile_id_index ON public.polls USING btree (profile_id);


--
-- Name: polls_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX polls_status_id_index ON public.polls USING btree (status_id);


--
-- Name: polls_story_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX polls_story_id_index ON public.polls USING btree (story_id);


--
-- Name: portfolios_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portfolios_active_index ON public.portfolios USING btree (active);


--
-- Name: profile_aliases_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profile_aliases_profile_id_index ON public.profile_aliases USING btree (profile_id);


--
-- Name: profiles_cw_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_cw_index ON public.profiles USING btree (cw);


--
-- Name: profiles_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_deleted_at_index ON public.profiles USING btree (deleted_at);


--
-- Name: profiles_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_domain_index ON public.profiles USING btree (domain);


--
-- Name: profiles_followers_count_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_followers_count_index ON public.profiles USING btree (followers_count);


--
-- Name: profiles_following_count_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_following_count_index ON public.profiles USING btree (following_count);


--
-- Name: profiles_indexable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_indexable_index ON public.profiles USING btree (indexable);


--
-- Name: profiles_is_private_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_is_private_index ON public.profiles USING btree (is_private);


--
-- Name: profiles_is_suggestable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_is_suggestable_index ON public.profiles USING btree (is_suggestable);


--
-- Name: profiles_key_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_key_id_index ON public.profiles USING btree (key_id);


--
-- Name: profiles_last_fetched_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_last_fetched_at_index ON public.profiles USING btree (last_fetched_at);


--
-- Name: profiles_no_autolink_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_no_autolink_index ON public.profiles USING btree (no_autolink);


--
-- Name: profiles_remote_url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_remote_url_index ON public.profiles USING btree (remote_url);


--
-- Name: profiles_sharedinbox_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_sharedinbox_index ON public.profiles USING btree ("sharedInbox");


--
-- Name: profiles_status_count_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_status_count_index ON public.profiles USING btree (status_count);


--
-- Name: profiles_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_status_index ON public.profiles USING btree (status);


--
-- Name: profiles_unlisted_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_unlisted_index ON public.profiles USING btree (unlisted);


--
-- Name: profiles_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_user_id_index ON public.profiles USING btree (user_id);


--
-- Name: profiles_username_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX profiles_username_index ON public.profiles USING btree (username);


--
-- Name: pulse_aggregates_period_bucket_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_aggregates_period_bucket_index ON public.pulse_aggregates USING btree (period, bucket);


--
-- Name: pulse_aggregates_period_type_aggregate_bucket_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_aggregates_period_type_aggregate_bucket_index ON public.pulse_aggregates USING btree (period, type, aggregate, bucket);


--
-- Name: pulse_aggregates_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_aggregates_type_index ON public.pulse_aggregates USING btree (type);


--
-- Name: pulse_entries_key_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_entries_key_hash_index ON public.pulse_entries USING btree (key_hash);


--
-- Name: pulse_entries_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_entries_timestamp_index ON public.pulse_entries USING btree ("timestamp");


--
-- Name: pulse_entries_timestamp_type_key_hash_value_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_entries_timestamp_type_key_hash_value_index ON public.pulse_entries USING btree ("timestamp", type, key_hash, value);


--
-- Name: pulse_entries_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_entries_type_index ON public.pulse_entries USING btree (type);


--
-- Name: pulse_values_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_values_timestamp_index ON public.pulse_values USING btree ("timestamp");


--
-- Name: pulse_values_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pulse_values_type_index ON public.pulse_values USING btree (type);


--
-- Name: push_subscriptions_subscribable_type_subscribable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX push_subscriptions_subscribable_type_subscribable_id_index ON public.push_subscriptions USING btree (subscribable_type, subscribable_id);


--
-- Name: remote_auth_instances_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auth_instances_active_index ON public.remote_auth_instances USING btree (active);


--
-- Name: remote_auth_instances_allowed_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auth_instances_allowed_index ON public.remote_auth_instances USING btree (allowed);


--
-- Name: remote_auth_instances_banned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auth_instances_banned_index ON public.remote_auth_instances USING btree (banned);


--
-- Name: remote_auth_instances_instance_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auth_instances_instance_id_index ON public.remote_auth_instances USING btree (instance_id);


--
-- Name: remote_auth_instances_root_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auth_instances_root_domain_index ON public.remote_auth_instances USING btree (root_domain);


--
-- Name: remote_auths_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auths_client_id_index ON public.remote_auths USING btree (client_id);


--
-- Name: remote_auths_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auths_domain_index ON public.remote_auths USING btree (domain);


--
-- Name: remote_auths_instance_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_auths_instance_id_index ON public.remote_auths USING btree (instance_id);


--
-- Name: remote_reports_action_taken_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX remote_reports_action_taken_at_index ON public.remote_reports USING btree (action_taken_at);


--
-- Name: report_comments_report_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX report_comments_report_id_index ON public.report_comments USING btree (report_id);


--
-- Name: reports_object_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reports_object_id_index ON public.reports USING btree (object_id);


--
-- Name: reports_object_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reports_object_type_index ON public.reports USING btree (object_type);


--
-- Name: reports_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reports_profile_id_index ON public.reports USING btree (profile_id);


--
-- Name: reports_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reports_user_id_index ON public.reports USING btree (user_id);


--
-- Name: status_archiveds_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_archiveds_profile_id_index ON public.status_archiveds USING btree (profile_id);


--
-- Name: status_archiveds_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_archiveds_status_id_index ON public.status_archiveds USING btree (status_id);


--
-- Name: status_edits_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_edits_profile_id_index ON public.status_edits USING btree (profile_id);


--
-- Name: status_edits_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_edits_status_id_index ON public.status_edits USING btree (status_id);


--
-- Name: status_hashtags_hashtag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_hashtags_hashtag_id_index ON public.status_hashtags USING btree (hashtag_id);


--
-- Name: status_hashtags_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_hashtags_profile_id_index ON public.status_hashtags USING btree (profile_id);


--
-- Name: status_hashtags_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_hashtags_status_id_index ON public.status_hashtags USING btree (status_id);


--
-- Name: status_hashtags_status_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_hashtags_status_visibility_index ON public.status_hashtags USING btree (status_visibility);


--
-- Name: status_views_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_views_profile_id_index ON public.status_views USING btree (profile_id);


--
-- Name: status_views_status_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_views_status_id_index ON public.status_views USING btree (status_id);


--
-- Name: status_views_status_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX status_views_status_profile_id_index ON public.status_views USING btree (status_profile_id);


--
-- Name: statuses_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_created_at_index ON public.statuses USING btree (created_at);


--
-- Name: statuses_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_deleted_at_index ON public.statuses USING btree (deleted_at);


--
-- Name: statuses_in_reply_or_reblog_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_in_reply_or_reblog_index ON public.statuses USING btree (in_reply_to_id, reblog_of_id);


--
-- Name: statuses_in_reply_to_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_in_reply_to_id_index ON public.statuses USING btree (in_reply_to_id);


--
-- Name: statuses_is_nsfw_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_is_nsfw_index ON public.statuses USING btree (is_nsfw);


--
-- Name: statuses_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_local_index ON public.statuses USING btree (local);


--
-- Name: statuses_place_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_place_id_index ON public.statuses USING btree (place_id);


--
-- Name: statuses_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_profile_id_index ON public.statuses USING btree (profile_id);


--
-- Name: statuses_reblog_of_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_reblog_of_id_index ON public.statuses USING btree (reblog_of_id);


--
-- Name: statuses_scope_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_scope_index ON public.statuses USING btree (scope);


--
-- Name: statuses_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_type_index ON public.statuses USING btree (type);


--
-- Name: statuses_uri_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_uri_index ON public.statuses USING btree (uri);


--
-- Name: statuses_url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_url_index ON public.statuses USING btree (url);


--
-- Name: statuses_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX statuses_visibility_index ON public.statuses USING btree (visibility);


--
-- Name: stories_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_active_index ON public.stories USING btree (active);


--
-- Name: stories_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_expires_at_index ON public.stories USING btree (expires_at);


--
-- Name: stories_is_archived_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_is_archived_index ON public.stories USING btree (is_archived);


--
-- Name: stories_local_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_local_index ON public.stories USING btree (local);


--
-- Name: stories_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_profile_id_index ON public.stories USING btree (profile_id);


--
-- Name: stories_public_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stories_public_index ON public.stories USING btree (public);


--
-- Name: story_views_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX story_views_profile_id_index ON public.story_views USING btree (profile_id);


--
-- Name: story_views_story_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX story_views_story_id_index ON public.story_views USING btree (story_id);


--
-- Name: telescope_entries_batch_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_batch_id_index ON public.telescope_entries USING btree (batch_id);


--
-- Name: telescope_entries_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_created_at_index ON public.telescope_entries USING btree (created_at);


--
-- Name: telescope_entries_family_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_family_hash_index ON public.telescope_entries USING btree (family_hash);


--
-- Name: telescope_entries_tags_tag_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_tags_tag_index ON public.telescope_entries_tags USING btree (tag);


--
-- Name: telescope_entries_type_should_display_on_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_type_should_display_on_index_index ON public.telescope_entries USING btree (type, should_display_on_index);


--
-- Name: user_devices_ip_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_devices_ip_index ON public.user_devices USING btree (ip);


--
-- Name: user_devices_user_agent_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_devices_user_agent_index ON public.user_devices USING btree (user_agent);


--
-- Name: user_devices_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_devices_user_id_index ON public.user_devices USING btree (user_id);


--
-- Name: user_domain_blocks_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_domain_blocks_domain_index ON public.user_domain_blocks USING btree (domain);


--
-- Name: user_domain_blocks_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_domain_blocks_profile_id_index ON public.user_domain_blocks USING btree (profile_id);


--
-- Name: user_email_forgots_email_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_email_forgots_email_sent_at_index ON public.user_email_forgots USING btree (email_sent_at);


--
-- Name: user_email_forgots_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_email_forgots_user_id_index ON public.user_email_forgots USING btree (user_id);


--
-- Name: user_filters_filter_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_filters_filter_type_index ON public.user_filters USING btree (filter_type);


--
-- Name: user_filters_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_filters_user_id_index ON public.user_filters USING btree (user_id);


--
-- Name: user_invites_profile_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invites_profile_id_index ON public.user_invites USING btree (profile_id);


--
-- Name: user_invites_used_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invites_used_at_index ON public.user_invites USING btree (used_at);


--
-- Name: user_invites_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invites_user_id_index ON public.user_invites USING btree (user_id);


--
-- Name: users_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_deleted_at_index ON public.users USING btree (deleted_at);


--
-- Name: users_has_interstitial_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_has_interstitial_index ON public.users USING btree (has_interstitial);


--
-- Name: users_language_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_language_index ON public.users USING btree (language);


--
-- Name: users_last_active_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_last_active_at_index ON public.users USING btree (last_active_at);


--
-- Name: users_register_source_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_register_source_index ON public.users USING btree (register_source);


--
-- Name: users_role_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_role_id_index ON public.users USING btree (role_id);


--
-- Name: users_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_status_index ON public.users USING btree (status);


--
-- Name: group_post_hashtags group_post_hashtags_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_group_id_foreign FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: group_post_hashtags group_post_hashtags_hashtag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_hashtag_id_foreign FOREIGN KEY (hashtag_id) REFERENCES public.group_hashtags(id) ON DELETE CASCADE;


--
-- Name: group_post_hashtags group_post_hashtags_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_profile_id_foreign FOREIGN KEY (profile_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: group_post_hashtags group_post_hashtags_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_post_hashtags
    ADD CONSTRAINT group_post_hashtags_status_id_foreign FOREIGN KEY (status_id) REFERENCES public.group_posts(id) ON DELETE CASCADE;


--
-- Name: group_stores group_stores_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_stores
    ADD CONSTRAINT group_stores_group_id_foreign FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: profile_aliases profile_aliases_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profile_aliases
    ADD CONSTRAINT profile_aliases_profile_id_foreign FOREIGN KEY (profile_id) REFERENCES public.profiles(id);


--
-- Name: telescope_entries_tags telescope_entries_tags_entry_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid) REFERENCES public.telescope_entries(uuid) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2 (Debian 17.2-1.pgdg120+1)
-- Dumped by pg_dump version 17.2 (Debian 17.2-1.pgdg120+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2014_10_12_000000_create_users_table	1
2	2014_10_12_100000_create_password_resets_table	1
3	2016_06_01_000001_create_oauth_auth_codes_table	1
4	2016_06_01_000002_create_oauth_access_tokens_table	1
5	2016_06_01_000003_create_oauth_refresh_tokens_table	1
6	2016_06_01_000004_create_oauth_clients_table	1
7	2016_06_01_000005_create_oauth_personal_access_clients_table	1
8	2018_04_16_000059_create_sessions_table	1
9	2018_04_16_002611_create_profiles_table	1
10	2018_04_16_005848_create_statuses_table	1
11	2018_04_16_011918_create_media_table	1
12	2018_04_17_012812_create_likes_table	1
13	2018_04_18_021047_create_followers_table	1
14	2018_04_18_044421_create_comments_table	1
15	2018_04_22_233721_create_web_subs_table	1
16	2018_04_26_000057_create_import_datas_table	1
17	2018_04_26_003259_create_import_jobs_table	1
18	2018_04_30_044507_create_jobs_table	1
19	2018_04_30_044539_create_failed_jobs_table	1
20	2018_05_04_054007_create_reports_table	1
21	2018_05_06_214815_create_hashtags_table	1
22	2018_05_06_215006_create_status_hashtags_table	1
23	2018_05_07_021835_create_avatars_table	1
24	2018_05_07_025743_create_notifications_table	1
25	2018_05_31_043327_create_bookmarks_table	1
26	2018_06_04_061435_update_notifications_table_add_polymorphic_relationship	1
27	2018_06_08_003624_create_mentions_table	1
28	2018_06_11_030049_add_filters_to_media_table	1
29	2018_06_14_001318_add_soft_deletes_to_models	1
30	2018_06_14_041422_create_email_verifications_table	1
31	2018_06_22_062621_create_report_comments_table	1
32	2018_06_22_062628_create_report_logs_table	1
33	2018_07_05_010303_create_account_logs_table	1
34	2018_07_12_054015_create_user_settings_table	1
35	2018_07_15_011916_add_2fa_to_users_table	1
36	2018_07_15_013106_create_user_filters_table	1
37	2018_08_08_100000_create_telescope_entries_table	1
38	2018_08_12_042648_update_status_table_change_caption_to_text	1
39	2018_08_22_022306_update_settings_table	1
40	2018_08_27_004653_update_media_table_add_alt_text	1
41	2018_09_02_042235_create_follow_requests_table	1
42	2018_09_02_043240_update_profile_table_add_ap_urls	1
43	2018_09_02_043609_create_instances_table	1
44	2018_09_10_024252_update_import_datas_table	1
45	2018_09_11_202435_create_activities_table	1
46	2018_09_18_043334_add_cw_desc_to_status	1
47	2018_09_19_060554_create_stories_table	1
48	2018_09_19_060611_create_story_reactions_table	1
49	2018_09_27_040314_create_collections_table	1
50	2018_09_30_051108_create_direct_messages_table	1
51	2018_10_02_040917_create_collection_items_table	1
52	2018_10_09_043717_update_status_visibility_defaults	1
53	2018_10_17_033327_update_status_add_scope_column	1
54	2018_10_17_233623_update_follower_table_add_remote_flags	1
55	2018_10_18_035552_update_media_add_alt_text	1
56	2018_10_25_030944_update_profile_table	1
57	2018_12_01_020238_add_type_to_status_table	1
58	2018_12_22_055940_add_account_status_to_profiles_table	1
59	2018_12_24_032921_add_delete_after_to_user_table	1
60	2018_12_30_065102_update_profiles_table_use_text_for_bio	1
61	2019_01_11_005556_update_profiles_table	1
62	2019_01_12_054413_stories	1
63	2019_01_22_030129_create_pages_table	1
64	2019_02_01_023357_add_remote_to_avatars_table	1
65	2019_02_07_004642_create_discover_categories_table	1
66	2019_02_07_021214_create_discover_category_hashtags_table	1
67	2019_02_08_192219_create_websockets_statistics_entries_table	1
68	2019_02_09_045935_create_circles_table	1
69	2019_02_09_045956_create_circle_profiles_table	1
70	2019_02_13_195702_add_indexes	1
71	2019_02_13_221138_add_soft_delete_indexes	1
72	2019_02_15_033323_create_user_invites_table	1
73	2019_03_02_023245_add_profile_id_to_status_hashtags_table	1
74	2019_03_06_065528_create_user_devices_table	1
75	2019_03_12_043935_add_snowflakeids_to_users_table	1
76	2019_03_31_191216_add_replies_count_to_statuses_table	1
77	2019_04_16_184644_add_layout_to_profiles_table	1
78	2019_04_25_200411_add_snowflake_ids_to_collections_table	1
79	2019_04_28_024733_add_suggestions_to_profiles_table	1
80	2019_05_04_174911_add_header_to_profiles_table	1
81	2019_06_06_032316_create_contacts_table	1
82	2019_06_16_051157_add_profile_ids_to_users_table	1
83	2019_07_05_034644_create_hashtag_follows_table	1
84	2019_07_08_045824_add_status_visibility_to_status_hashtags_table	1
85	2019_07_11_234836_create_profile_sponsors_table	1
86	2019_07_16_010525_remove_web_subs_table	1
87	2019_08_07_184030_create_places_table	1
88	2019_08_12_074612_add_unique_to_statuses_table	1
89	2019_09_09_032757_add_object_id_to_statuses_table	1
90	2019_09_21_015556_add_language_to_users_table	1
91	2019_12_10_023604_create_newsroom_table	1
92	2019_12_25_042317_update_stories_table	1
93	2020_02_14_063209_create_mod_logs_table	1
94	2020_04_11_045459_add_fetched_at_to_profiles_table	1
95	2020_04_13_045435_create_uikit_table	1
96	2020_06_30_180159_create_media_tags_table	1
97	2020_07_25_230100_create_media_blocklists_table	1
98	2020_08_18_022520_add_remote_url_to_stories_table	1
99	2020_11_14_221947_add_type_to_direct_messages_table	1
100	2020_12_01_073200_add_indexes_to_likes_table	1
101	2020_12_03_050018_create_account_interstitials_table	1
102	2020_12_13_203138_add_uuids_to_failed_jobs_table	1
103	2020_12_13_203646_add_providers_column_to_oauth_clients_table	1
104	2020_12_14_103423_create_login_links_table	1
105	2020_12_24_063410_create_status_views_table	1
106	2020_12_25_220825_add_status_profile_id_to_likes_table	1
107	2020_12_27_013953_add_text_column_to_media_table	1
108	2020_12_27_040951_add_skip_optimize_to_media_table	1
109	2020_12_28_012026_create_status_archiveds_table	1
110	2020_12_30_070905_add_last_active_at_to_users_table	1
111	2021_01_14_034521_add_cache_locks_table	1
112	2021_01_15_050602_create_instance_actors_table	1
113	2021_01_25_011355_add_cdn_url_to_avatars_table	1
114	2021_04_24_045522_add_active_to_stories_table	1
115	2021_04_28_060450_create_config_caches_table	1
116	2021_05_12_042153_create_user_pronouns_table	1
117	2021_07_23_062326_add_compose_settings_to_user_settings_table	1
118	2021_07_29_014835_create_polls_table	1
119	2021_07_29_014849_create_poll_votes_table	1
120	2021_08_04_095125_create_groups_table	1
121	2021_08_04_095143_create_group_members_table	1
122	2021_08_04_095238_create_group_posts_table	1
123	2021_08_04_100435_create_group_roles_table	1
124	2021_08_16_072457_create_group_invitations_table	1
125	2021_08_16_100034_create_group_interactions_table	1
126	2021_08_17_073839_create_group_reports_table	1
127	2021_08_23_062246_update_stories_table_fix_expires_at_column	1
128	2021_08_30_050137_add_software_column_to_instances_table	1
129	2021_09_26_112423_create_group_blocks_table	1
130	2021_09_29_023230_create_group_limits_table	1
131	2021_10_01_083917_create_group_categories_table	1
132	2021_10_09_004230_create_group_hashtags_table	1
133	2021_10_09_004436_create_group_post_hashtags_table	1
134	2021_10_13_002033_create_group_stores_table	1
135	2021_10_13_002041_create_group_events_table	1
136	2021_10_13_002124_create_group_activity_graphs_table	1
137	2021_11_06_100552_add_more_settings_to_user_settings_table	1
138	2021_11_09_105629_add_action_to_account_interstitials_table	1
139	2022_01_03_052623_add_last_status_at_to_profiles_table	1
140	2022_01_08_103817_add_index_to_followers_table	1
141	2022_01_16_060052_create_portfolios_table	1
142	2022_01_19_025041_create_custom_emoji_table	1
143	2022_02_13_091135_add_missing_reblog_of_id_types_to_statuses_table	1
144	2022_03_09_042023_add_ldap_columns_to_users_table	1
145	2022_04_08_065311_create_cache_table	1
146	2022_04_20_061915_create_conversations_table	1
147	2022_05_26_034550_create_live_streams_table	1
148	2022_06_03_051308_add_object_column_to_follow_requests_table	1
149	2022_09_01_000000_fix_webfinger_profile_duplicate_accounts	1
150	2022_09_01_043002_generate_missing_profile_webfinger	1
151	2022_09_19_093029_fix_double_json_encoded_settings_in_usersettings_table	1
152	2022_10_07_045520_add_reblog_of_id_index_to_statuses_table	1
153	2022_10_07_055133_remove_old_compound_index_from_statuses_table	1
154	2022_10_07_072311_add_status_id_index_to_bookmarks_table	1
155	2022_10_07_072555_add_status_id_index_to_direct_messages_table	1
156	2022_10_07_072859_add_status_id_index_to_mentions_table	1
157	2022_10_07_073337_add_indexes_to_reports_table	1
158	2022_10_07_110644_add_item_id_and_item_type_indexes_to_notifications_table	1
159	2022_10_09_043758_fix_cdn_url_in_avatars_table	1
160	2022_10_31_043257_add_actors_last_synced_at_to_instances_table	1
161	2022_11_24_065214_add_register_source_to_users_table	1
162	2022_11_30_123940_update_avatars_table_remove_cdn_url_unique_constraint	1
163	2022_12_05_064156_add_key_id_index_to_profiles_table	1
164	2022_12_13_092726_create_admin_invites_table	1
165	2022_12_18_012352_add_status_id_index_to_media_table	1
166	2022_12_18_034556_add_remote_media_index_to_media_table	1
167	2022_12_18_133815_add_default_value_to_admin_invites_table	1
168	2022_12_20_075729_add_action_index_to_notifications_table	1
169	2022_12_27_013417_add_can_trend_to_hashtags_table	1
170	2022_12_27_102053_update_hashtag_count	1
171	2022_12_31_034627_fix_account_status_deletes	1
172	2023_01_15_041933_add_missing_profile_id_to_users_table	1
173	2023_01_19_141156_fix_bookmark_visibility	1
174	2023_01_21_124608_fix_duplicate_profiles	1
175	2023_01_29_034653_create_status_edits_table	1
176	2023_02_04_053028_fix_cloud_media_paths	1
177	2023_03_19_050342_add_notes_to_instances_table	1
178	2023_04_20_092740_fix_account_blocks	1
179	2023_04_24_101904_create_remote_reports_table	1
180	2023_05_03_023758_update_postgres_visibility_defaults_on_statuses_table	1
181	2023_05_03_042219_fix_postgres_hashtags	1
182	2023_05_07_091703_add_edited_at_to_statuses_table	1
183	2023_05_13_045228_remove_unused_columns_from_notifications_table	1
184	2023_05_13_123119_remove_status_entities_from_statuses_table	1
185	2023_05_15_050604_create_autospam_custom_tokens_table	1
186	2023_05_19_102013_add_enable_atom_feed_to_user_settings_table	1
187	2023_05_29_072206_create_user_app_settings_table	1
188	2023_06_07_000001_create_pulse_tables	1
189	2023_06_10_031634_create_import_posts_table	1
190	2023_06_28_103008_add_user_id_index_to_profiles_table	1
191	2023_07_07_025757_create_remote_auths_table	1
192	2023_07_07_030427_create_remote_auth_instances_table	1
193	2023_07_11_080040_add_show_reblogs_to_followers_table	1
194	2023_08_07_021252_create_profile_aliases_table	1
195	2023_08_08_045430_add_moved_to_profile_id_to_profiles_table	1
196	2023_08_25_050021_add_indexable_column_to_profiles_table	1
197	2023_09_12_044900_create_admin_shadow_filters_table	1
198	2023_11_13_062429_add_followers_count_index_to_profiles_table	1
199	2023_11_16_124107_create_hashtag_related_table	1
200	2023_11_26_082439_add_state_and_score_to_places_table	1
201	2023_12_04_041631_create_push_subscriptions_table	1
202	2023_12_05_092152_add_active_deliver_to_instances_table	1
203	2023_12_08_074345_add_direct_object_urls_to_statuses_table	1
204	2023_12_13_060425_add_uploaded_to_s3_to_import_posts_table	1
205	2023_12_16_052413_create_user_domain_blocks_table	1
206	2023_12_19_081928_create_job_batches_table	1
207	2023_12_21_103223_purge_deleted_status_hashtags	1
208	2023_12_21_104103_create_default_domain_blocks_table	1
209	2023_12_27_081801_create_user_roles_table	1
210	2023_12_27_082024_add_has_roles_to_users_table	1
211	2024_01_09_052419_create_parental_controls_table	1
212	2024_01_16_073327_create_curated_registers_table	1
213	2024_01_20_091352_create_curated_register_activities_table	1
214	2024_01_22_090048_create_user_email_forgots_table	1
215	2024_02_24_093824_add_has_responded_to_curated_registers_table	1
216	2024_02_24_105641_create_curated_register_templates_table	1
217	2024_03_02_094235_create_profile_migrations_table	1
218	2024_03_08_122947_add_shared_inbox_attribute_to_instances_table	1
219	2024_03_08_123356_add_shared_inboxes_to_instances_table	1
220	2024_05_20_062706_update_group_posts_table	1
221	2024_05_20_063638_create_group_comments_table	1
222	2024_05_20_073054_create_group_likes_table	1
223	2024_05_20_083159_create_group_media_table	1
224	2024_05_31_090555_update_instances_table_add_index_to_nodeinfo_last_fetched_at	1
225	2024_06_03_232204_add_url_index_to_statuses_table	1
226	2024_06_19_084835_add_total_local_posts_to_config_cache	1
227	2024_07_22_065800_add_expo_token_to_users_table	1
228	2024_07_29_081002_add_storage_used_to_users_table	1
229	2024_09_18_093322_add_notify_shares_to_users_table	1
230	2024_10_06_035032_modify_caption_field_in_media_table	1
231	2024_10_15_044935_create_moderated_profiles_table	1
232	2025_01_18_061532_fix_local_statuses	1
233	2025_01_28_102016_create_app_registers_table	1
234	2025_02_10_194847_fix_non_nullable_postgres_errors	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 234, true);


--
-- PostgreSQL database dump complete
--

