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

import moduleFields    from './store_fields';
import moduleGroups    from './store_groups';
import moduleProjects  from './store_projects';
import moduleStates    from './store_states';
import moduleTemplates from './store_templates';

/**
 * Global store for 'Projects' page.
 */
module.exports = new Vuex.Store({

    modules: {
        projects: moduleProjects,
        groups: moduleGroups,
        templates: moduleTemplates,
        states: moduleStates,
        fields: moduleFields,
    },
});
