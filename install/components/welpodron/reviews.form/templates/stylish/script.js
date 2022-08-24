((window)=>{
// По сути является легкой версией DialogNative
// TODO: Focus
class ImgPreview {
  constructor(element, config = {}) {
    this.element = element;

    this.element.addEventListener("click", this.handleClick);
  }

  handleClick = (evt) => {
    evt.preventDefault();

    const dialog = document.createElement("dialog");
    const dialogCloseBtn = document.createElement("button");
		const dialogImgWrapper = document.createElement("div");
    const dialogImg = document.createElement("img");

    dialogCloseBtn.addEventListener(
      "click",
      (evt) => {
        evt.currentTarget.closest("dialog").remove();
      },
      { once: true }
    );

    dialogImg.src = this.element.getAttribute("data-preview-src");

    dialog.classList.add(
      this.element.getAttribute("data-preview-dialog-class")
    );
    dialogCloseBtn.classList.add(
      this.element.getAttribute("data-preview-dialog-btn-close-class")
    );
    dialogImg.classList.add(
      this.element.getAttribute("data-preview-dialog-img-class")
    );

    dialog.appendChild(dialogCloseBtn);
dialogImgWrapper.appendChild(dialogImg);
    dialog.appendChild(dialogImgWrapper);

    document.body.appendChild(dialog);
    dialog.showModal();
  };
}

window.welpodron = window.welpodron || {};
welpodron.imgPreview = ImgPreview;
})(window)