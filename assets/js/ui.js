//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

/**
 * Displays modal message box.
 *
 * @param {string}   header       Text of the message box header.
 * @param {string}   message      Text of the message box body.
 * @param {string}   iconGlyph    FontAwesome icon class.
 * @param {string}   iconClass    Additional class to apply to the icon.
 * @param {boolean}  singleButton Whether to create one-button ('OK') or two-buttons ('Yes'/'No') box.
 * @param {function} [onClose]    Optional handler to call when the message box is closed.
 */
const messageBox = (header, message, iconGlyph, iconClass, singleButton, onClose) => {

    // Unique ID of the '<dialog>' element.
    const id = '__etraxis_' + Math.random().toString(36).substr(2);

    const buttons = singleButton
        ? `<button data-id="yes">${i18n['button.close']}</button>`
        : `<button data-id="yes">${i18n['button.yes']}</button>` +
          `<button data-id="no">${i18n['button.no']}</button>`;

    const template = `
        <dialog id=${id} class="messagebox">
            <div class="modal-header">
                <span class="pull-after fa fa-remove" title="${i18n['button.close']}"></span>
                <div>${header}</div>
            </div>
            <div class="modal-body">
                <p class="pull-before fa fa-3x ${iconGlyph} ${iconClass}"></p>
                <p class="message"><span>${message}</span></p>
            </div>
            <div class="modal-footer text-right">
                ${buttons}
            </div>
        </dialog>`;

    document.querySelector('body').insertAdjacentHTML('beforeend', template);

    const modal = document.getElementById(id);

    dialogPolyfill.registerDialog(modal);

    const btnYes   = modal.querySelector('.modal-footer button[data-id="yes"]');
    const btnNo    = modal.querySelector('.modal-footer button[data-id="no"]');
    const btnClose = modal.querySelector('.modal-header .fa-remove');

    // Button 'Yes' is clicked.
    btnYes.addEventListener('click', () => {
        modal.close('yes');
    });

    // Button 'No' is clicked.
    if (btnNo) {
        btnNo.addEventListener('click', () => {
            modal.close('no');
        });
    }

    // The 'x' button in the header is clicked.
    btnClose.addEventListener('click', () => {
        modal.close('no');
    });

    // 'Esc' is pressed.
    modal.addEventListener('cancel', () => {
        modal.returnValue = 'no';
    });

    // Dialog is closed.
    modal.addEventListener('close', () => {
        modal.parentNode.removeChild(modal);

        if (singleButton || modal.returnValue === 'yes') {
            if (typeof onClose === 'function') {
                onClose();
            }
        }
    });

    modal.showModal();
};

/**
 * Displays error message box (alternative to JavaScript "alert").
 *
 * @param {string}   message   Error message.
 * @param {function} [onClose] Optional handler to call when the message box is closed.
 */
exports.alert = (message, onClose) =>
    messageBox(i18n['error'], message, 'fa-times-circle', 'attention', true, onClose);

/**
 * Displays informational message box (alternative to JavaScript "alert").
 *
 * @param {string}   message   Informational message.
 * @param {function} [onClose] Optional handler to call when the message box is closed.
 */
exports.info = (message, onClose) =>
    messageBox('eTraxis', message, 'fa-info-circle', 'pending', true, onClose);

/**
 * Displays confirmation message box (alternative to JavaScript "confirm").
 *
 * @param {string}   message     Confirmation message.
 * @param {function} [onConfirm] Optional handler to call when the message box is closed with confirmation.
 */
exports.confirm = (message, onConfirm) =>
    messageBox('eTraxis', message, 'fa-question-circle', 'pending', false, onConfirm);

/**
 * Blocks UI from user interaction.
 */
exports.block = () => {

    const id = '__etraxis_blockui';
    const template = `<dialog id=${id} class="blockui">${i18n['text.please_wait']}</dialog>`;

    if (!document.getElementById(id)) {

        document.querySelector('body').insertAdjacentHTML('beforeend', template);

        const modal = document.getElementById(id);

        dialogPolyfill.registerDialog(modal);

        modal.addEventListener('close', () => {
            modal.parentNode.removeChild(modal);
        });

        modal.addEventListener('cancel', event => {
            event.preventDefault();
        });

        modal.showModal();
    }
};

/**
 * Unblocks UI.
 */
exports.unblock = () => {

    const modal = document.getElementById('__etraxis_blockui');

    if (modal) {
        modal.close();
    }
};
