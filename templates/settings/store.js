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
 * Current user's profile.
 */
eTraxis.store = {

    profile: {
        email: null,
        fullname: null,
        locale: null,
        theme: null,
        timezone: null,
    },

    /**
     * Loads user's profile from the server.
     */
    loadProfile() {
        ui.block();
        axios.get(url('/api/my/profile'))
            .then(response => {
                this.profile.email    = response.data.email;
                this.profile.fullname = response.data.fullname;
                this.profile.locale   = response.data.locale;
                this.profile.theme    = response.data.theme;
                this.profile.timezone = response.data.timezone;
            })
            .catch(exception => ui.getErrors(exception))
            .then(() => ui.unblock());
    },
};
