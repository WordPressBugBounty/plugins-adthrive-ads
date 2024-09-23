const cb = 'adthrive'; // Needed to prevent other Caching Plugins from delaying execution

function checkEmail(value) {
	const matched = value.match(
		/((?=([a-z0-9._!#$%+^&*()[\]<>-]+))\2@[a-z0-9._-]+\.[a-z0-9._-]+)/gi
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

async function hashEmail(email) {
	const hashObj = {
		sha256Hash: '',
		sha1Hash: '',
	};

	const supportedEnvironment =
		!('msCrypto' in window) &&
		location.protocol === 'https:' &&
		'crypto' in window &&
		'TextEncoder' in window;

	if (supportedEnvironment) {
		const msgUint8 = new TextEncoder().encode(email);
		const [sha256Hash, sha1Hash] = await Promise.all([
			convertToSpecificHashType('SHA-256', msgUint8),
			convertToSpecificHashType('SHA-1', msgUint8),
		]);

		hashObj.sha256Hash = sha256Hash;
		hashObj.sha1Hash = sha1Hash;
	}
	return hashObj;
}

async function convertToSpecificHashType(shaHashType, msgUint8) {
	const hashBuffer = await crypto.subtle.digest(shaHashType, msgUint8);
	const hashArray = Array.from(new Uint8Array(hashBuffer));
	const hashHex = hashArray
		.map((b) => ('00' + b.toString(16)).slice(-2))
		.join('');
	return hashHex;
}

function hasHashes(hashObj) {
	let allNonEmpty = true;

	Object.keys(hashObj).forEach((key) => {
		if (hashObj[key].length === 0) {
			allNonEmpty = false;
		}
	});

	return allNonEmpty;
}

function removeEmailAndReplaceHistory(paramsArray, index, siteUrl) {
	paramsArray.splice(index, 1);
	const url = '?' + paramsArray.join('&') + siteUrl.hash;
	history.replaceState(null, '', url);
}

async function detectEmails() {
	const adthrivePlainTextKey = 'adt_ei';
	const adthriveHashedKey = 'adt_eih';
	const convertKitHashedKey = 'sh_kit';
	const localStorageKey = 'adt_ei';
	const localStorageSrcKey = 'adt_emsrc';
	const siteUrl = new URL(window.location.href);
	const paramsArray = Array.from(siteUrl.searchParams.entries()).map(
		(param) => `${param[0]}=${param[1]}`
	);

	let plainTextQueryParam;
	let hashedQueryParam;

	const allowedHashedKeys = [adthriveHashedKey, convertKitHashedKey];

	paramsArray.forEach((param, index) => {
		const decodedParam = decodeURIComponent(param);
		const [key, value] = decodedParam.split('=');

		if (key === adthrivePlainTextKey) {
			plainTextQueryParam = { value, index, emsrc: 'url' };
		}

		if (allowedHashedKeys.includes(key)) {
			const emsrc = key === convertKitHashedKey ? 'urlhck' : 'urlh';
			hashedQueryParam = { value, index, emsrc };
		}
	});

	if (plainTextQueryParam) {
		if (validateEmail(plainTextQueryParam.value)) {
			hashEmail(plainTextQueryParam.value).then((hashObj) => {
				if (hasHashes(hashObj)) {
					const data = {
						value: hashObj,
						created: Date.now(),
					};
					localStorage.setItem(localStorageKey, JSON.stringify(data));
					localStorage.setItem(
						localStorageSrcKey,
						plainTextQueryParam.emsrc
					);
				}
			});
		}
	} else if (hashedQueryParam) {
		const data = {
			value: {
				sha256Hash: hashedQueryParam.value,
				sha1Hash: '',
			},
			created: Date.now(),
		};

		localStorage.setItem(localStorageKey, JSON.stringify(data));
		localStorage.setItem(localStorageSrcKey, hashedQueryParam.emsrc);
	}

	plainTextQueryParam &&
		removeEmailAndReplaceHistory(
			paramsArray,
			plainTextQueryParam.index,
			siteUrl
		);

	hashedQueryParam &&
		removeEmailAndReplaceHistory(
			paramsArray,
			hashedQueryParam.index,
			siteUrl
		);
}

module.exports = {
	checkEmail,
	validateEmail,
	trimInput,
	hashEmail,
	hasHashes,
	removeEmailAndReplaceHistory,
	detectEmails,
	cb,
};
