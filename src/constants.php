<?php

namespace Skynet;

/**
 *
 */
const DEFAULT_PARSE_SKYLINK_OPTIONS = [
	'fromSubdomain' => false,
	'includePath'   => false,
	'onlyPath'      => false,
];
/**
 *
 */
const URI_SKYNET_PREFIX             = "sia://";
/**
 *
 */
const URI_HANDSHAKE_PREFIX          = "hns://";
/**
 *
 */
const SKYLINK_MATCHER               = "([a-zA-Z0-9_-]{46})";
/**
 *
 */
const SKYLINK_MATCHER_SUBDOMAIN     = "([a-z0-9_-]{55})";
/**
 *
 */
const SKYLINK_DIRECT_REGEX          = '^' . SKYLINK_MATCHER . '$';
/**
 *
 */
const SKYLINK_PATHNAME_REGEX        = '^/?' . SKYLINK_MATCHER . '((/.*)?)$';
/**
 *
 */
const SKYLINK_SUBDOMAIN_REGEX       = '^' . SKYLINK_MATCHER_SUBDOMAIN . '(\\..*)?$';
/**
 *
 */
const SKYLINK_DIRECT_MATCH_POSITION = 1;
/**
 *
 */
const SKYLINK_PATH_MATCH_POSITION  = 2;
/**
 *
 */
const REGISTRY_TYPE_WITHOUT_PUBKEY = 1;
/**
 *
 */
const ED25519_PREFIX              = 'ed25519:';
/**
 *
 */
const SPECIFIER_LEN               = 16;
/**
 *
 */
const PUBLIC_KEY_SIZE             = 32;
/**
 *
 */
const RAW_SKYLINK_SIZE            = 34;
/**
 *
 */
const BASE32_ENCODED_SKYLINK_SIZE = 55;
/**
 *
 */
const BASE64_ENCODED_SKYLINK_SIZE = 46;
/**
 *
 */
const ERR_SKYLINK_INCORRECT_SIZE = "skylink has incorrect size";
/**
 *
 */
const DEFAULT_SKYNET_PORTAL_URL        = "https://siasky.net";
/**
 *
 */
const SIGNATURE_LENGTH                 = SODIUM_CRYPTO_SIGN_BYTES;
/**
 *
 */
const PUBLIC_KEY_LENGTH                = SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES * 2;
/**
 *
 */
const DEFAULT_GET_ENTRY_TIMEOUT           = 5;
/**
 *
 */
const REGEX_REVISION_WITH_QUOTES          = '/"revision":\s*"([0-9]+)"/';
/**
 *
 */
const REGEX_REVISION_NO_QUOTES            = '/"revision":\s*([0-9]+)/';
/**
 *
 */
const TUS_CHUNK_SIZE                      = ( 1 << 22 ) * 10;
/**
 *
 */
const DEFAULT_TUS_RETRY_DELAYS            = [ 0, 5_000, 15_000, 60_000, 300_000, 600_000 ];
/**
 *
 */
const JSON_RESPONSE_VERSION               = 2;
/**
 *
 */
const PORTAL_FILE_FIELD_NAME              = 'file';
/**
 *
 */
const PORTAL_DIRECTORY_FILE_FIELD_NAME    = 'files[]';
/**
 *
 */
const TUS_PARALLEL_UPLOADS                = 2;
/**
 *
 */
const DEFAULT_HANDSHAKE_MAX_ATTEMPTS      = 150;
/**
 *
 */
const DEFAULT_HANDSHAKE_ATTEMPTS_INTERVAL = 100;
/**
 *
 */
const DISCOVERABLE_BUCKET_TWEAK_VERSION = 1;
/**
 *
 */
const SEED_WORDS_LENGTH          = 13;
/**
 *
 */
const SEED_LENGTH                = 16;
/**
 *
 */
const CHECKSUM_WORDS_LENGTH      = 2;
/**
 *
 */
const PHRASE_LENGTH              = SEED_WORDS_LENGTH + CHECKSUM_WORDS_LENGTH;
/**
 *
 */
const SALT_ROOT_DISCOVERABLE_KEY = 'root discoverable key';
