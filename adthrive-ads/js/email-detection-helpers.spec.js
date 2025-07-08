const {
  checkEmail,
  trimInput,
  validateEmail,
  removeQueryParamsAndUpdateHistory,
  detectEmails,
} = require('./email-detection-helpers');

const setupUrl = (urlString) => {
  const { pathname, search, hash } = new URL(urlString);
  const cleanUrl = `${pathname}${search}${hash}`;
  window.history.replaceState(null, '', cleanUrl);
};

describe('email-detection-helpers', () => {
  describe('trimInput', () => {
    const cases = ['   hello', 'hello    ', 'hel    lo', '   hel    lo    '];

    it.each(cases)('should trim whitespace - %s', (value) => {
      expect(trimInput(value)).toBe('hello');
    });
  });

  describe('checkEmail', () => {
    const invalidEmails = ['hello', 'hello@', 'hello@world', 'hello@world.'];
    const validEmails = [
      'foo@example.com',
      'test@foo.com',
      'another@one.com',
      'aNoTher@onE.cOm',
    ];

    it.each(invalidEmails)(
      'should return an empty string if no email is found - %s',
      (str) => {
        expect(checkEmail(str)).toBe('');
      },
    );

    it.each(validEmails)(
      'should return the matched email if found - %s',
      (str) => {
        expect(checkEmail(str)).toBe(str);
      },
    );
  });

  describe('validateEmail', () => {
    const invalidEmails = [
      '  heLLo',
      'hellO  @',
      '  hel  lo@worlD ',
      'hEllo   @   World  .',
    ];
    const validEmails = [
      { input: '   FOO@EXAMPLE.COM  ', output: 'foo@example.com' },
      { input: ' TEST@EXampLE   .coM', output: 'test@example.com' },
      { input: 'An OtH eR @oNe.CoM  ', output: 'another@one.com' },
    ];

    it.each(invalidEmails)(
      'should return an empty string if no email is found - %s',
      (str) => {
        expect(validateEmail(str)).toBe('');
      },
    );

    it.each(validEmails)(
      'should return the matched email if found - %s',
      (values) => {
        expect(validateEmail(values.input)).toBe(values.output);
      },
    );
  });

  describe('removeQueryParamsAndUpdateHistory', () => {
    it('should remove specified query parameters from the URL', () => {
      setupUrl(
        'http://localhost/page.html?adt_ei=hello@world.com&adt_eih=sha256hashgoeshere&sh_kit=anotherhash&foo=bar',
      );

      removeQueryParamsAndUpdateHistory(
        ['adt_ei', 'adt_eih'],
        window.location.href,
      );

      expect(window.location.href).toBe(
        'http://localhost/page.html?sh_kit=anotherhash&foo=bar',
      );
    });

    it('should not modify the URL if no parameters are specified', () => {
      setupUrl('http://localhost/page.html');
      removeQueryParamsAndUpdateHistory([], window.location.href);
      expect(window.location.href).toBe('http://localhost/page.html');
    });
  });

  describe('detectEmails', () => {
    beforeEach(() => {
      window.adthrive = {
        cmd: {
          push: jest.fn((cb) => cb()),
        },
        identityApi: jest.fn(),
      };
    });

    afterEach(() => {
      jest.clearAllMocks();
    });

    it('should detect adt_ei query param and invoke identity api', async () => {
      setupUrl('http://localhost?adt_ei=hello@world.com');
      await detectEmails();
      expect(window.adthrive.identityApi).toHaveBeenCalledWith(
        expect.objectContaining({
          plainText: 'hello@world.com',
          source: 'url',
        }),
        expect.any(Function),
      );
    });

    it('should detect adt_eih query param and invoke identity api', async () => {
      setupUrl('http://localhost/page.html?adt_eih=sha256hashgoeshere');
      await detectEmails();
      expect(window.adthrive.identityApi).toHaveBeenCalledWith(
        expect.objectContaining({
          sha256: 'sha256hashgoeshere',
          source: 'urlh',
        }),
        expect.any(Function),
      );
    });

    it('should detect sh_kit query param and invoke identity api', async () => {
      setupUrl('http://localhost/page.html?sh_kit=sha256hashgoeshere');
      await detectEmails();
      expect(window.adthrive.identityApi).toHaveBeenCalledWith(
        expect.objectContaining({
          sha256: 'sha256hashgoeshere',
          source: 'urlhck',
        }),
        expect.any(Function),
      );
    });

    it('should not invoke identity api if no email query params are present', async () => {
      setupUrl('http://localhost/page.html?foo=bar');
      await detectEmails();
      expect(window.adthrive.identityApi).not.toHaveBeenCalled();
    });

    it('should prioritize adt_ei over adt_eih and sh_kit', async () => {
      setupUrl(
        'http://localhost/page.html?adt_ei=hello@world.com&adt_eih=sha256hashgoeshere&sh_kit=sha256hashgoeshere',
      );
      await detectEmails();
      expect(window.adthrive.identityApi).toHaveBeenCalledWith(
        expect.objectContaining({
          plainText: 'hello@world.com',
          source: 'url',
        }),
        expect.any(Function),
      );
    });

    it('should prioritize adt_eih over sh_kit if adt_ei is not present', async () => {
      setupUrl(
        'http://localhost/page.html?adt_eih=sha256hashgoeshere&sh_kit=sha256hashgoeshere',
      );
      await detectEmails();
      expect(window.adthrive.identityApi).toHaveBeenCalledWith(
        expect.objectContaining({
          sha256: 'sha256hashgoeshere',
          source: 'urlh',
        }),
        expect.any(Function),
      );
    });

    it('should remove adt_ei, adt_eih, and sh_kit query params from the URL', async () => {
      setupUrl(
        'http://localhost/page.html?adt_ei=hello@world.com&adt_eih=sha256hashgoeshere&sh_kit=sha256hashgoeshere',
      );
      await detectEmails();
      expect(window.location.href).toBe('http://localhost/page.html');
      expect(window.location.search).toBe('');
    });
  });
});
