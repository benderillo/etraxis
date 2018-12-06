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

window.eTraxis = {};

Vue.options.delimiters = ['${', '}'];
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
