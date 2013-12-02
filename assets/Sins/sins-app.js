/**
 * Sins application javascript.
 *
 * @package    Sins
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/
$(function($) {
    // class definitions
    app.classes = {};
    // viewmodel instances
    app.viewModels = {};
    
    // dropdown viewmodel
    app.classes.vmDropdown = function (init) {
        var self = this;
        var parentDefaults = {
            type:     null,
            active:   null,
            disabled: null,
            link:     null,
            faIcon:   null,
            text:     '',
            items:    []
        };
        var itemDefaults = {
            type:     null,
            active:   null,
            disabled: null,
            link:     null,
            text:     '',
        };
        this.items   = null;
        this.parents = null;
        this.header = null;

        if (init.items === undefined) {
            // this is a menu
            self.parents = [];
            $.each(init.parents, function (i, parent) {
                if (parent.type === 'submenu' ) {
                    $.each(parent.items, function (j, item) {
                        parent.items[j] = $.extend({}, itemDefaults, item);
                    });
                }
                self.parents.push($.extend({}, parentDefaults, parent));
            });
        } else {
            // this is a dropdown
            self.items = [];
            $.each(init.items, function (i, item) {
                self.items.push($.extend({}, itemDefaults, item));
            });
        }
    };

    // load a test
    app.runTest = function () {
        // work out what to do
        // request what we want
        // render it
    };

    // initialisation for topmenu
    app.local.topmenu = {
        parents: [
            {link: 'myurl', faIcon: 'fa-home', text:'Home'},
            {type: 'submenu', text:'Home2 <b class="caret"></b>', items: [
                {link: 'myurl', text:'<i class="fa fa-circle fa-lg fa-fw"></i> Home'},
                {link: 'myurl2', text:'<i class="fa fa-circle-o fa-lg fa-fw"></i> Home2'},
                {link: 'myurl3', text:'<i class="fa fa-circle fa-lg fa-fw"></i> Home3'},
                {link: 'myurl4', text:'<i class="fa fa-circle fa-lg fa-fw"></i> Home4'}
            ]},
            {link: 'myurl3', faIcon: 'fa-circle-o', text:'Home3'},
            {link: 'myurl4', faIcon: 'fa-circle', text:'Home4'}
        ]
    };

    // initialise the top menu
    app.viewModels.topmenu = new app.classes.vmDropdown(app.local.topmenu);
    ko.applyBindings(app.viewModels.topmenu, document.getElementById('topmenu'))

});
