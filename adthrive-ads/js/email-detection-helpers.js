const cb = 'adthrive'; // Needed to prevent other Caching Plugins from delaying execution

const EMAIL_PARAM_MAP = {
  adt_ei: {
    identityApiKey: 'plainText',
    source: 'url',
    type: 'plaintext',
    priority: 1,
  },
  adt_eih: {
    identityApiKey: 'sha256',
    source: 'urlh',
    type: 'hashed',
    priority: 2,
  },
  sh_kit: {
    identityApiKey: 'sha256',
    source: 'urlhck',
    type: 'hashed',
    priority: 3,
  },
};

const EMAIL_PARAMS = Object.keys(EMAIL_PARAM_MAP);

function checkEmail(value) {
  const matched = value.match(
    /((?=([a-z0-9._!#$%+^&*()[\]<>-]+))\2@[a-z0-9._-]+\.[a-z0-9._-]+)/gi,
  );
  if (!matched) {
    return '';
  }
  return matched[0];
}

function validateEmail(value) {
  return checkEmail(trimInput(value.toLowerCase()));
}

function trimInput(value) {
  return value.replace(/\s/g, '');
}

/**
 * Removes specified query parameters from a given URL and updates the browser history.
 * @param {string[]} keysToRemove - The query parameter keys to remove.
 * @param {string} url - The URL to modify.
 */
function removeQueryParamsAndUpdateHistory(keysToRemove, url) {
  const updatedUrl = new URL(url);
  keysToRemove.forEach((key) => updatedUrl.searchParams.delete(key));
  history.replaceState(null, '', updatedUrl.toString());
}

/**
 * Detects email parameters in the current URL and processes them if valid.
 * It checks for both plaintext and hashed email parameters, and if found,
 * it invokes the AdThrive identity API with the appropriate parameters.
 * This function also removes the email parameters from the URL
 * to prevent them from being processed again.
 * @returns {Promise<void>}
 */
async function detectEmails() {
  const siteUrl = new URL(window.location.href);
  const searchParams = siteUrl.searchParams;

  let matchedParam = null;

  const sortedParams = Object.entries(EMAIL_PARAM_MAP)
    .sort(([, a], [, b]) => a.priority - b.priority)
    .map(([key]) => key);

  for (const key of sortedParams) {
    const value = searchParams.get(key);
    const config = EMAIL_PARAM_MAP[key];

    if (!value || !config) continue;

    const decodedValue = decodeURIComponent(value);
    const isPlain = config.type === 'plaintext' && validateEmail(decodedValue);
    const isHash = config.type === 'hashed' && decodedValue;

    if (isPlain || isHash) {
      matchedParam = { value: decodedValue, config };
      break;
    }
  }

  if (matchedParam) {
    const { value, config } = matchedParam;

    window.adthrive = window.adthrive || {};
    window.adthrive.cmd = window.adthrive.cmd || [];

    window.adthrive.cmd.push(function () {
      window.adthrive.identityApi(
        {
          source: config.source,
          [config.identityApiKey]: value,
        },
        ({ success, data }) => {
          if (success) {
            window.adthrive.log(
              'info',
              'Plugin',
              'detectEmails',
              `Identity API called with ${config.type} email: ${value}`,
              data,
            );
          } else {
            window.adthrive.log(
              'warning',
              'Plugin',
              'detectEmails',
              `Failed to call Identity API with ${config.type} email: ${value}`,
              data,
            );
          }
        },
      );
    });
  }

  removeQueryParamsAndUpdateHistory(EMAIL_PARAMS, siteUrl);
}

module.exports = {
  checkEmail,
  validateEmail,
  trimInput,
  removeQueryParamsAndUpdateHistory,
  detectEmails,
  cb,
};
