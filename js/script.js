(() => {
  // Core EXT

  window.welpodon = window.welpodon || {};

  class WelpodronDialogNative {
    constructor(element, config = {}) {
      this.element = element;
      this.once = config.once || this.element.getAttribute("data-once");
      this.forceShowModal =
        config.forceShowModal ||
        this.element.getAttribute("data-force-show-modal");

      this.outsideControls = [
        ...document.querySelectorAll(
          `[data-dialog-native-action][data-dialog-native-id="${this.element.id}"]:not(#${this.element.id} *)`
        ),
      ];

      this.insideControls = [
        ...this.element.querySelectorAll(
          `[data-dialog-native-action][data-dialog-native-id="${this.element.id}"]`
        ),
      ];

      // track opening
      this.dialogAttrObserver = new MutationObserver((mutations, observer) => {
        mutations.forEach(async (mutation) => {
          if (mutation.attributeName === "open") {
            const dialog = mutation.target;

            const isOpen = dialog.hasAttribute("open");
            if (!isOpen) return;

            dialog.removeAttribute("inert");

            // set focus
            const focusTarget = dialog.querySelector("[autofocus]");
            focusTarget
              ? focusTarget.focus()
              : dialog.querySelector("button").focus();

            this.insideControls.forEach((control) => {
              control.removeEventListener("click", this.handleControlClick);
              control.addEventListener("click", this.handleControlClick);
            });

            document.body.style.overflow = "hidden";
          }
        });
      });

      this.dialogAttrObserver.observe(this.element, {
        attributes: true,
      });

      // remove loading attribute
      // prevent page load @keyframes playing
      Promise.allSettled(
        this.element.getAnimations().map((animation) => animation.finished)
      ).then(() => {
        this.element.removeAttribute("loading");
      });

      this.element.removeEventListener("click", this.handleClick);
      this.element.removeEventListener("close", this.handleClose);
      this.element.addEventListener("click", this.handleClick);
      this.element.addEventListener("close", this.handleClose);

      this.outsideControls.forEach((control) => {
        control.removeEventListener("click", this.handleControlClick);
        control.addEventListener("click", this.handleControlClick);
      });

      if (this.forceShowModal != null) {
        this.showModal();
      }
    }

    close = () => {
      this.element.close("dismiss");
    };

    show = () => {
      this.element.show();
    };

    showModal = () => {
      this.element.showModal();
    };

    handleControlClick = (evt) => {
      evt.preventDefault();

      const { currentTarget } = evt;
      const action = currentTarget.getAttribute("data-dialog-native-action");
      const actionArgs = currentTarget.getAttribute(
        "data-dialog-native-action-args"
      );

      if (this[action] instanceof Function) return this[action](actionArgs);
    };

    handleClick = ({ target, currentTarget }) => {
      if (target === currentTarget) currentTarget.close("dismiss");
    };

    handleClose = ({ currentTarget }) => {
      currentTarget.setAttribute("inert", "");

      Promise.allSettled(
        currentTarget.getAnimations().map((animation) => animation.finished)
      ).then(() => {
        const currentlyOpennedDialogs = document.querySelector("dialog[open]");
        if (!currentlyOpennedDialogs) {
          document.body.style.overflow = "";
        }

        this.insideControls.forEach((control) => {
          control.removeEventListener("click", this.handleControlClick);
        });

        if (this.once != null) {
          this.outsideControls.forEach((control) => {
            control.removeEventListener("click", this.handleControlClick);
            control.parentNode.removeChild(control);
          });
          this.element.removeEventListener("click", this.handleClick);
          this.element.removeEventListener("close", this.handleClose);
          this.element.parentNode.removeChild(this.element);
        }
      });
    };
  }

  welpodon.dialogNative = WelpodronDialogNative;

  // Core EXT end

  // Forms API

  window.welpodon = window.welpodon || {};

  welpodon.forms = {};

  class FormField {
    constructor(element, control) {
      this.element = element;
      this.control = control;

      this.value =
        this.control.type === "checkbox" || this.control.type === "radio"
          ? this.control.checked
          : this.control.value;

      const elementName = this.element.getAttribute("data-form-field-name");

      this.name = elementName ? elementName.trim() : "";
      this.control.name = this.name;
    }

    getValue = () => {
      return this.value;
    };

    reset = () => {
      this.value = null;
      if (this.control.type === "checkbox" || this.control.type === "radio") {
        this.control.checked = false;
      } else {
        this.control.value = null;
      }
    };

    setValidity = (msg) => {
      // Так как hidden не поддерживает reportValidity то просто выведем в консоль что не так
      // TODO: Лучше перенести ошибки такого типа в ошибку формы FORM_FIELD_ERROR
      console.error(msg);
    };

    disable = () => {
      this.disabled = true;
      this.control.disabled = true;
    };

    enable = () => {
      this.disabled = false;
      this.control.disabled = false;
    };
  }

  welpodon.forms.formField = FormField;

  class FormFilesDropzoneField extends welpodon.forms.formField {
    defaultConfig = {
      showcaseElSelector: "[data-files-dropzone-showcase]",
      dropzoneElSelector: "[data-files-dropzone-zone]",
      dropzoneBeforeDropDisplayElSelector: "[data-files-dropzone-drop-display]",
      dropzoneDroppingDispayElSelector:
        "[data-files-dropzone-dropping-display]",
    };

    constructor(element, control, config = {}) {
      super(element, control);

      this.element = element;

      this.value = config.defaultValue || {};

      // TODO: Лучше убрать blur и остлеживать изменения в размере объекта value чтобы когда будут меняться его ключи, то customvalidiy пропадает
      this.control.removeEventListener("change", this.handleChange);
      this.control.addEventListener("change", this.handleChange);
      this.control.removeEventListener("blur", this.handleBlur);
      this.control.addEventListener("blur", this.handleBlur);

      this.showcaseEl =
        config.showcaseEl ||
        this.element.querySelector(this.defaultConfig.showcaseElSelector);

      if (this.showcaseEl) {
        this.showcaseEls = {};
      }

      this.dropzoneEl =
        config.dropzoneEl ||
        this.element.querySelector(this.defaultConfig.dropzoneElSelector);

      if (this.dropzoneEl) {
        this.dropzoneEl.removeEventListener("drop", this.handleDrop);
        this.dropzoneEl.addEventListener("drop", this.handleDrop);
        this.dropzoneEl.removeEventListener("dragover", this.handleDragOver);
        this.dropzoneEl.addEventListener("dragover", this.handleDragOver);
        this.dropzoneEl.removeEventListener("dragleave", this.handleDragLeave);
        this.dropzoneEl.addEventListener("dragleave", this.handleDragLeave);

        this.dropzoneBeforeDropDisplayEl =
          config.dropzoneBeforeDropDisplayEl ||
          this.dropzoneEl.querySelector(
            this.defaultConfig.dropzoneBeforeDropDisplayElSelector
          );
        this.dropzoneDroppingDispayEl =
          config.dropzoneDroppingDispayEl ||
          this.dropzoneEl.querySelector(
            this.defaultConfig.dropzoneDroppingDispayElSelector
          );
      }

      // config options
      this.maxFiles =
        config.maxFiles || this.element.getAttribute("data-files-max-amount");
      this.maxFileSize =
        config.maxFileSize || this.element.getAttribute("data-file-max-size");

      let filesSupportedFormats = this.element.getAttribute(
        "data-files-supported"
      );

      if (filesSupportedFormats) {
        filesSupportedFormats = filesSupportedFormats
          .trim()
          .split(",")
          .filter((str) => str.trim());
      }

      this.filesSupportedFormats =
        config.filesSupportedFormats || filesSupportedFormats;
    }

    getUUID = () => {
      return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
        (
          c ^
          (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
        ).toString(16)
      );
    };

    handleChange = (evt) => {
      if (this.disabled) return;
      const files = evt.currentTarget.files;
      if (files) {
        for (let i = 0; i < files.length; i++) {
          if (
            this.maxFiles != null &&
            Object.keys(this.value).length >= this.maxFiles
          ) {
            return;
          }

          if (!this.validateFile(files.item(i))) {
            return;
          }

          const fileId = this.getUUID();

          this.value[fileId] = files.item(i);

          // Showcase appended item
          if (this.showcaseEl) {
            const divEl = document.createElement("div");
            divEl.setAttribute("data-files-dropzone-showcase-item", "");
            const buttonDelEl = document.createElement("button");
            buttonDelEl.type = "button";
            buttonDelEl.ariaLevel = "Отменить загрузку этого файла";
            buttonDelEl.setAttribute("data-files-dropzone-control", "");
            buttonDelEl.setAttribute("data-files-dropzone-action", "remove");
            buttonDelEl.setAttribute("data-files-dropzone-action-args", fileId);
            buttonDelEl.addEventListener("click", this.handleClick);
            divEl.appendChild(buttonDelEl);
            this.showcaseEl.appendChild(divEl);
            this.showcaseEls[fileId] = divEl;
            const reader = new FileReader();
            reader.addEventListener(
              "load",
              (evt) => this.handleLoad(evt, fileId),
              {
                once: true,
              }
            );
            reader.readAsDataURL(files.item(i));
          }
        }
      }
      evt.currentTarget.value = null;
    };

    handleLoad = (evt, fileId) => {
      const imgEl = document.createElement("img");
      imgEl.src = evt.target.result;

      this.showcaseEls[fileId].appendChild(imgEl);
    };

    remove = (fileId) => {
      delete this.value[fileId];

      if (this.showcaseEls) {
        const showcaseEl = this.showcaseEls[fileId];

        if (showcaseEl) {
          showcaseEl.parentNode.removeChild(showcaseEl);
        }

        delete this.showcaseEls[fileId];
      }
    };

    handleClick = (evt) => {
      evt.preventDefault();
      this.control.setCustomValidity("");
      if (this.disabled) return;

      const { currentTarget } = evt;
      const action = currentTarget.getAttribute("data-files-dropzone-action");
      const actionArgs = currentTarget.getAttribute(
        "data-files-dropzone-action-args"
      );

      if (this[action] instanceof Function) return this[action](actionArgs);
    };

    handleDrop = (evt) => {
      evt.preventDefault();

      this.control.setCustomValidity("");
      if (this.disabled) return;

      if (this.dropzoneBeforeDropDisplayEl && this.dropzoneDroppingDispayEl) {
        this.dropzoneDroppingDispayEl.style.display = "none";
        this.dropzoneBeforeDropDisplayEl.style.display = "block";
      }

      if (evt.dataTransfer.items) {
        // Use DataTransferItemList interface to access the file(s)
        [...evt.dataTransfer.items].forEach((item, i) => {
          // If dropped items aren't files, reject them
          if (item.kind === "file") {
            const file = item.getAsFile();

            if (
              this.maxFiles != null &&
              Object.keys(this.value).length >= this.maxFiles
            ) {
              this.setValidity("Загружено максимальное число файлов");
              return;
            }

            if (!this.validateFile(file)) {
              return;
            }

            const fileId = this.getUUID();

            this.value[fileId] = file;

            // Showcase appended item
            if (this.showcaseEl) {
              const divEl = document.createElement("div");
              divEl.setAttribute("data-files-dropzone-showcase-item", "");
              const buttonDelEl = document.createElement("button");
              buttonDelEl.type = "button";
              buttonDelEl.ariaLevel = "Отменить загрузку этого файла";
              buttonDelEl.setAttribute("data-files-dropzone-control", "");
              buttonDelEl.setAttribute("data-files-dropzone-action", "remove");
              buttonDelEl.setAttribute(
                "data-files-dropzone-action-args",
                fileId
              );
              buttonDelEl.addEventListener("click", this.handleClick);
              divEl.appendChild(buttonDelEl);
              this.showcaseEl.appendChild(divEl);
              this.showcaseEls[fileId] = divEl;
              const reader = new FileReader();
              reader.addEventListener(
                "load",
                (evt) => this.handleLoad(evt, fileId),
                {
                  once: true,
                }
              );
              reader.readAsDataURL(file);
            }
          }
        });
      }
    };

    handleDragOver = (evt) => {
      evt.preventDefault();

      if (this.disabled) return;

      if (this.dropzoneBeforeDropDisplayEl && this.dropzoneDroppingDispayEl) {
        this.dropzoneDroppingDispayEl.style.display = "block";
        this.dropzoneBeforeDropDisplayEl.style.display = "none";
      }
    };

    handleDragLeave = (evt) => {
      evt.preventDefault();

      if (this.disabled) return;

      if (this.dropzoneBeforeDropDisplayEl && this.dropzoneDroppingDispayEl) {
        this.dropzoneDroppingDispayEl.style.display = "none";
        this.dropzoneBeforeDropDisplayEl.style.display = "block";
      }
    };

    validateFile = (file) => {
      if (this.maxFileSize != null && file.size > this.maxFileSize) {
        this.setValidity(
          `Размер файла ${file.name} - ${(file.size / 1024 / 1024).toFixed(
            2
          )}МБ превышает максимально допустимый размер - ${(
            this.maxFileSize /
            1024 /
            1024
          ).toFixed(2)}МБ`
        );
        return false;
      }

      if (
        this.filesSupportedFormats != null &&
        !this.filesSupportedFormats.includes(file.type)
      ) {
        this.setValidity(
          `Файл ${file.name} имеет недопустимый тип: ${
            file.type
          }, доступные типы файлов: ${this.filesSupportedFormats.join(",")}`
        );
        return false;
      }

      return true;
    };

    reset = () => {
      this.value = {};
      this.control.value = null;

      if (this.showcaseEls) {
        Object.values(this.showcaseEls).forEach((showcaseEl) => {
          showcaseEl.parentNode.removeChild(showcaseEl);
        });

        this.showcaseEls = {};
      }
    };

    getValue = () => {
      return Object.values(this.value);
    };

    setValidity = (msg) => {
      this.control.setCustomValidity(msg);
      this.control.reportValidity();
    };

    handleBlur = (evt) => {
      this.control.setCustomValidity("");
    };
  }

  welpodon.forms.formFilesDropzoneField = FormFilesDropzoneField;

  class FormImputable extends welpodon.forms.formField {
    constructor(element, control) {
      super(element, control);

      this.initEventListeners();
    }

    getErrors = () => {
      const errors = [];
      const validity = this.control.validity;

      if (validity.patternMismatch)
        errors.push(
          `Значение поля не удовлетворяет маске: ${
            this.control.title ? this.control.title : this.control.pattern
          }`
        );

      if (validity.rangeOverflow)
        errors.push(`Значение поля больше ${this.control.max}`);

      if (validity.rangeUnderflow)
        errors.push(`Значение поля меньше ${this.control.min}`);

      if (validity.stepMismatch)
        errors.push(
          `Значение поля не соответствует шагу: ${this.control.step}`
        );

      if (validity.tooLong) errors.push("Значение поля слишком длинное");

      if (validity.tooShort) errors.push("Значение поля слишком короткое");

      if (validity.typeMismatch)
        errors.push(
          `Значение поля не соответствует типу: ${this.control.type}`
        );

      if (validity.valueMissing) errors.push("Поле обязательно для заполнения");

      return errors;
    };

    initEventListeners = () => {
      if (
        this.control.tagName.toLowerCase() === "input" ||
        this.control.tagName.toLowerCase() === "textarea"
      ) {
        if (this.control.type !== "checkbox" && this.control.type !== "radio") {
          this.control.removeEventListener("input", this.handleChange);
          this.control.addEventListener("input", this.handleChange);
          return;
        }
      }

      this.control.removeEventListener("change", this.handleChange);
      this.control.addEventListener("change", this.handleChange);
    };

    setValidity = (msg) => {
      this.control.setCustomValidity(msg);
      this.control.reportValidity();
    };

    reset = () => {
      if (this.control.type === "checkbox" || this.control.type === "radio") {
        this.control.checked = false;
      } else {
        this.control.value = "";
      }

      this.value =
        this.control.type === "checkbox" || this.control.type === "radio"
          ? this.control.checked
          : this.control.value;
    };

    handleChange = (evt) => {
      if (this.disabled) return;

      this.value =
        this.control.type === "checkbox" || this.control.type === "radio"
          ? evt.currentTarget.checked
          : this.control.type === "file"
          ? evt.currentTarget.files
          : evt.currentTarget.value;

      const errors = this.getErrors();

      if (errors.length) {
        this.setValidity(errors.join(". \n"));
      } else {
        this.control.setCustomValidity("");
      }
    };
  }

  welpodon.forms.formImputable = FormImputable;

  class FormRadiosGroupField {
    constructor(element) {
      // TODO: Может быть лучше добавить Input type hidden группе чтобы облегчить setCustomValidity?
      this.element = element;

      const elementName = this.element.getAttribute("data-form-field-name");

      this.name = elementName ? elementName.trim() : "";

      this.controls = [
        ...this.element.querySelectorAll(
          `input[type="radio"][name="${this.name}"]`
        ),
      ];

      this.controls.forEach((control) => {
        control.removeEventListener("change", this.handleChange);
        control.addEventListener("change", this.handleChange);
      });

      this.activeControl =
        this.controls.find((control) => control.checked) || this.controls[0];

      this.value = this.activeControl.value;
    }

    getErrors = () => {
      const errors = [];

      const validity = this.controls[0].validity;

      if (validity.valueMissing) errors.push("Поле обязательно для заполнения");

      return errors;
    };

    getValue = () => {
      this.activeControl = this.controls.find((control) => control.checked);

      this.value = this.activeControl.value;

      return this.value;
    };

    reset = () => {
      this.activeControl = this.controls[0];
      this.activeControl.checked = true;
      this.value = this.activeControl.value;
    };

    setValidity = (msg) => {
      this.controls[0].setCustomValidity(msg);
      this.controls[0].reportValidity();
    };

    handleChange = (evt) => {
      this.activeControl = evt.currentTarget;

      const errors = this.getErrors();

      if (errors.length) {
        this.setValidity(errors.join(". \n"));
      } else {
        this.controls[0].setCustomValidity("");
      }
    };

    disable = () => {
      this.disabled = true;
      this.controls.forEach((control) => {
        control.disabled = true;
      });
    };

    enable = () => {
      this.disabled = false;
      this.controls.forEach((control) => {
        control.disabled = false;
      });
    };
  }

  welpodon.forms.formRadiosGroupField = FormRadiosGroupField;

  class FormFieldsFactory {
    create = (element) => {
      switch (element.getAttribute("data-form-field-type")) {
        case "hidden":
          return new welpodon.forms.formField(
            element,
            element.querySelector('input[type="hidden"]')
          );
        case "text":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="text"]')
          );
        case "number":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="number"]')
          );
        case "email":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="email"]')
          );
        case "tel":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="tel"]')
          );
        case "checkbox":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="checkbox"]')
          );
        case "radio":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="radio"]')
          );
        case "file":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector('input[type="file"]')
          );
        case "textarea":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector("textarea")
          );
        case "select":
          return new welpodon.forms.formImputable(
            element,
            element.querySelector("select")
          );
        // Dropzone update
        case "filesDropzone":
          return new welpodon.forms.formFilesDropzoneField(
            element,
            element.querySelector('input[type="file"]')
          );
        // Radios update
        case "radios":
          return new welpodon.forms.formRadiosGroupField(element);
      }
    };
  }

  welpodon.forms.formFieldsFactory = new FormFieldsFactory();

  class FormFieldset {
    constructor(element) {
      this.element = element;
      this.name = this.element.dataset.name
        ? this.element.dataset.name.trim()
        : null;
      this.fields = [];
      this.fieldsets = [];
      this.data = {};

      [...this.element.querySelectorAll("[data-form-fieldset]")]
        .filter(
          (el) =>
            el.parentElement.closest("[data-form-fieldset]") === this.element
        )
        .forEach((el) => {
          this.fieldsets.push(new FormFieldset(el));
        });

      this.element
        .querySelectorAll(
          `[data-form-field-name][data-form-field-type][data-form-field]:not(:scope [data-form-fieldset] *)`
        )
        .forEach((element) => {
          let instance = welpodon.forms.formFieldsFactory.create(element);

          if (instance) {
            this.fields.push(instance);
          }

          instance = null;
        });
    }

    getFieldsFlat = () => {
      let temp = [...this.fields];

      this.fieldsets.forEach((fieldset) => {
        temp = [...temp, fieldset.getFieldsFlat()];
      });

      return temp;
    };

    getData = () => {
      this.fields.forEach((field) => {
        if (field.name && field.value != null) {
          this.data[field.name] = field.getValue();
        }
      });

      this.fieldsets.forEach((fieldset) => {
        if (fieldset.name) {
          const data = fieldset.getData();

          if (Object.keys(data).length) {
            this.data[fieldset.name] = data;
          }
        } else {
          this.data = { ...this.data, ...fieldset.getData() };
        }
      });

      return this.data;
    };
  }

  welpodon.forms.formFieldset = FormFieldset;

  class Form {
    constructor(element, config = {}) {
      this.element = element;

      // TODO: errorsEl и successEl можно генерить если их нет и автоматически добавлять в форму как и в случае с капчей?
      this.errorsEl =
        config.errorsEl || this.element.querySelector("[data-form-errors]");

      this.successEl =
        config.successEl || this.element.querySelector("[data-form-success]");

      if (this.element.getAttribute("data-allow-default") == null) {
        this.element.removeEventListener("reset", this.handleReset);
        this.element.addEventListener("reset", this.handleReset);
        this.element.removeEventListener("submit", this.handleSubmit);
        this.element.addEventListener("submit", this.handleSubmit);
      }

      this.action = this.element.action;
      this.fieldsets = [];

      [...this.element.querySelectorAll("[data-form-fieldset]")]
        .filter((el) => !el.parentElement.closest("[data-form-fieldset]"))
        .forEach((el) => {
          this.fieldsets.push(new welpodon.forms.formFieldset(el));
        });

      this.fields = [];

      this.fieldsets.forEach((fieldset) => {
        this.fields = [...this.fields, ...fieldset.getFieldsFlat()];
      });

      this.captchaSiteKey = this.element.getAttribute("data-captcha");
      if (this.captchaSiteKey != null) {
        // В случае если никаких контейнеров не найдено то рендерим капчу прямо в начало формы
        this.captchaContainerEl =
          config.captchaContainerEl ||
          this.element.querySelector("[data-captcha-container]");

        if (!this.captchaContainerEl) {
          this.captchaContainerEl = document.createElement("div");
          this.captchaContainerEl.setAttribute("data-captcha-container", "");
          this.element.insertBefore(
            this.captchaContainerEl,
            this.element.firstChild
          );
        }

        this.captchaLoaded = this.getDefferedPromise();

        if (grecaptcha) {
          grecaptcha.ready(() => {
            // Пока грузится капча блочим всю форму
            this.disable();
            this.captchaLoaded.resolve();
          });
        }

        this.captchaLoaded.then(() => {
          // Капча загрузилась и произошел рендер делаем анблок формы
          this.widgetCaptchaId = grecaptcha.render(this.captchaContainerEl, {
            sitekey: this.captchaSiteKey,
          });
          this.enable();
        });
      }
    }

    getDefferedPromise = () => {
      let resolver, promise;
      promise = new Promise((resolve, reject) => {
        resolver = resolve;
      });
      promise.resolve = resolver;
      return promise;
    };

    // Thanks to therealparmesh / object-to-formdata
    // Source: https://github.com/therealparmesh/object-to-formdata

    isUndefined = (value) => {
      return value === undefined;
    };

    isNull = (value) => {
      return value === null;
    };

    isBoolean = (value) => {
      return typeof value === "boolean";
    };

    isObject = (value) => {
      return value === Object(value);
    };

    isArray = (value) => {
      return Array.isArray(value);
    };

    isDate = (value) => {
      return value instanceof Date;
    };

    isBlob = (value, isReactNative) => {
      return isReactNative
        ? this.isObject(value) && !this.isUndefined(value.uri)
        : this.isObject(value) &&
            typeof value.size === "number" &&
            typeof value.type === "string" &&
            typeof value.slice === "function";
    };

    isFile = (value, isReactNative) => {
      return (
        this.isBlob(value, isReactNative) &&
        typeof value.name === "string" &&
        (this.isObject(value.lastModifiedDate) ||
          typeof value.lastModified === "number")
      );
    };

    initCfg = (value) => {
      return this.isUndefined(value) ? false : value;
    };

    serialize = (obj, cfg, fd, pre) => {
      cfg = cfg || {};
      fd = fd || new FormData();

      cfg.indices = this.initCfg(cfg.indices);
      cfg.nullsAsUndefineds = this.initCfg(cfg.nullsAsUndefineds);
      cfg.booleansAsIntegers = this.initCfg(cfg.booleansAsIntegers);
      cfg.allowEmptyArrays = this.initCfg(cfg.allowEmptyArrays);
      cfg.noFilesWithArrayNotation = this.initCfg(cfg.noFilesWithArrayNotation);
      cfg.dotsForObjectNotation = this.initCfg(cfg.dotsForObjectNotation);

      const isReactNative = typeof fd.getParts === "function";

      if (this.isUndefined(obj)) {
        return fd;
      } else if (this.isNull(obj)) {
        if (!cfg.nullsAsUndefineds) {
          fd.append(pre, "");
        }
      } else if (this.isBoolean(obj)) {
        if (cfg.booleansAsIntegers) {
          fd.append(pre, obj ? 1 : 0);
        } else {
          fd.append(pre, obj);
        }
      } else if (this.isArray(obj)) {
        if (obj.length) {
          obj.forEach((value, index) => {
            let key = pre + "[" + (cfg.indices ? index : "") + "]";

            if (
              cfg.noFilesWithArrayNotation &&
              this.isFile(value, isReactNative)
            ) {
              key = pre;
            }

            this.serialize(value, cfg, fd, key);
          });
        } else if (cfg.allowEmptyArrays) {
          fd.append(pre + "[]", "");
        }
      } else if (this.isDate(obj)) {
        fd.append(pre, obj.toISOString());
      } else if (this.isObject(obj) && !this.isBlob(obj, isReactNative)) {
        Object.keys(obj).forEach((prop) => {
          const value = obj[prop];

          if (this.isArray(value)) {
            while (
              prop.length > 2 &&
              prop.lastIndexOf("[]") === prop.length - 2
            ) {
              prop = prop.substring(0, prop.length - 2);
            }
          }

          const key = pre
            ? cfg.dotsForObjectNotation
              ? pre + "." + prop
              : pre + "[" + prop + "]"
            : prop;

          this.serialize(value, cfg, fd, key);
        });
      } else {
        fd.append(pre, obj);
      }

      return fd;
    };

    getData = (customData = []) => {
      const data = [...customData];

      this.fieldsets.forEach((fieldset) => {
        let dataObj = {};
        let fieldsetData = fieldset.getData();
        if (fieldset.name) {
          dataObj[fieldset.name] = fieldsetData;
        } else {
          dataObj = fieldsetData;
        }
        data.push(dataObj);
      });

      return Object.assign({}, ...data);
    };

    getFormData = (customData = []) => {
      return this.serialize(this.getData(customData));
    };

    isHTML = (str) => {
      const doc = new DOMParser().parseFromString(str, "text/html");
      return [...doc.body.childNodes].some((node) => node.nodeType === 1);
    };

    renderString = (str, container, config = {}) => {
      const replace = config.replace;
      const templateElement = document.createElement("template");
      templateElement.innerHTML = str;
      const fragment = templateElement.content;
      fragment.querySelectorAll("script").forEach((sciptTag) => {
        const scriptParentNode = sciptTag.parentNode;
        scriptParentNode.removeChild(sciptTag);
        const script = document.createElement("script");
        script.text = sciptTag.text;
        scriptParentNode.append(script);
      });
      if (replace) {
        return container.replaceChildren(fragment);
      }

      return container.appendChild(fragment);
    };

    handleSubmit = (evt) => {
      evt.preventDefault();

      // TODO: disable all fields
      if (this.action) {
        if (this.disabled) {
          return;
        }

        this.disable();

        let customData = [];

        if (this.widgetCaptchaId != null) {
          customData = [
            {
              "g-recaptcha-response": grecaptcha.getResponse(
                this.widgetCaptchaId
              ),
            },
          ];
        }

        fetch(this.action, {
          method: "POST",
          body: this.getFormData(customData),
        })
          .then((responce) => {
            return responce.json();
          })
          .then((data) => {
            // Обычные контроллеры отправляют объект в формате {data,errors,status}
            // Подробнее: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=6436&LESSON_PATH=3913.3516.5062.3750.6436
            if (data.status === "error") {
              this.enable();
              data.errors.forEach((error) => {
                if (error.code === "FORM_GENERAL_ERROR") {
                  if (this.isHTML(error.message) && this.errorsEl) {
                    return this.renderString(error.message, this.errorsEl);
                  }
                }

                if (error.code === "FIELD_VALIDATION_ERROR") {
                  const field = this.fields.find((field) =>
                    field.name.includes(error.customData)
                  );
                  if (field) {
                    return field.setValidity(error.message);
                  }
                }

                console.error(error);
              });
            }

            if (data.status === "success") {
              this.reset();
              // TODO: Мб стоит рендерить ответ прямо в body?
              this.enable();
              if (
                this.isHTML(data.data) &&
                !data.errors.length &&
                this.successEl
              ) {
                return this.renderString(data.data, this.successEl);
              }

              console.log(data);
            }
          })
          .catch((error) => {
            console.error(error);
          })
          .finally(() => {
            // TODO: enable all fields
            this.enable();
          });
      }
    };

    handleReset = (evt) => {
      evt.preventDefault();
      this.reset();
    };

    disable = () => {
      this.disabled = true;
      this.fields.forEach((field) => {
        field.disable();
      });
    };

    enable = () => {
      this.disabled = false;
      this.fields.forEach((field) => {
        field.enable();
      });
    };

    reset = () => {
      this.fields.forEach((field) => {
        field.reset();
      });
      if (this.widgetCaptchaId != null) {
        grecaptcha.reset(this.widgetCaptchaId);
      }
    };

    send = () => {
      BX.ajax
        .runAction("welpodron:reviews.receiver.save", {
          data: this.getFormData(),
        })
        .then(function (data) {
          console.log(data);
        })
        .catch((err) => {
          console.error(err);
        });
    };
  }

  welpodon.forms.form = Form;

  // Forms API END
})();
