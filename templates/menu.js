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

import ui  from 'utilities/ui';
import url from 'utilities/url';

/**
 * Main menu (navigation).
 */
new Vue({
    el: 'nav',

    data: {
        // Whether the main menu is visible.
        isMenuHidden: true,
    },

    methods: {
        /**
         * Toggles visibility of the main menu.
         */
        toggleMenu() {
            this.isMenuHidden = !this.isMenuHidden;
        },

        /**
         * Asks for logout confirmation.
         */
        logout() {
            ui.confirm(i18n['confirm.logout'], () => {
                location.href = url('/logout');
            });
        },
    },
});
