/**
 * Sins application javascript.
 *
 * @package    Sins
 * @link       https://github.org/MrAnchovy/Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/
$(function ($) {
    // class definitions
    app.classes = {};
    // viewmodel instances
    app.viewModels = {};

    app.panels = {
        /**
         * Get a jQuery object containing the body element.
        **/
        body: function (id) {
            return $('#'+id+' .panel-body');
        },

        /**
         * Get a jQuery object containing the title element.
        **/
        title: function (id) {
            return $('#'+id+' .panel-title');
        }
    };

    /**
     * Test report viewmodel.
    **/
    app.classes.vmTestReport = function () {
        var self = this;
        
        // stack used to insert totals in group footers back into headers
        var groupStack = [];

        // report lines
        this.lines = [];


        function loadTotalise(lines) {
            $.each(lines, function (i, line) {
                switch (line.type) {

                    case 'GroupStart' :
                        // get the index of the current line and push it on the
                        // stack so we can retrieve it at the end of the group
                        groupStack.push(i);
                        break;

                    case 'GroupEnd' :
                        // get the start line for the current group off the stack
                        var startLine = groupStack.pop();
                        // add the summaries from the current line to the header line
                        lines[startLine].count = line.count;
                        lines[startLine].time  = line.time;
                        lines[startLine].level = groupStack.length;
                        break;

                } // end switch
            }); // end $.each(lines ...
            return lines;
        }

        function loadReport(lines) {
            var report = [];
            $.each(lines, function (i, line) {

                switch (line.type) {
                    case 'ReportStart' :
                        report.push('<h3>' + line.title + '</h3>');
                        report.push('<ul class="report">');
                        break;

                    case 'ReportEnd' :
                        report.push('</ul>');
                        break;

                    case 'GroupStart' :
                        var css;
                        var label = '';
                        if (line.count.pass) {
                            css = 'pass';
                            label = label + '<span class="label label-success">' + line.count.pass
                                + (line.count.pass === 1 ? ' pass' : ' passes') + '</span>';
                        }
                        if (line.count.skip) {
                            css = 'skip';
                            label = label + ' <span class="label label-warning">' + line.count.skip
                                + (line.count.skip === 1 ? ' skip' : ' skips') + '</span>';
                        }
                        if (line.count.fail) {
                            css = 'fail';
                            label = label + ' <span class="label label-danger">' + line.count.fail
                                + (line.count.fail === 1 ? ' fail' : ' fails') + '</span>';
                        }
                        report.push('<li><div class="report-group-header ' + css + ' level-' + line.level +'">'
                            + line.title.split('_').join(' ')
                            + '<span class="pull-right">' + label + '</span></div>');
                        report.push('<ul class="report-group report-group-' + line.groupType + '">');
                        break;

                    case 'GroupEnd' :
                        report.push('</ul></li>');
                        break;

                    case 'test' :
                        switch (line.result) {
                            case 'Pass' :
                                report.push('<li class="report-test pass">&#x2713; ' + line.title + '</li>');
                                break;
                            case 'Skip' :
                                report.push('<li class="report-test skip">? ' + line.title + '</li>');
                                break;
                            case 'Fail' :
                                report.push('<li class="report-test fail">&#x2717; ' + line.title + '</li>');
                                break;
                        }
                        break;
                }
            });
            return report.join("\n");
        }

        /**
         * Populate the model from a test report received from the api.
        **/
        this.load = function (lines) {
            console.log(loadReport(loadTotalise(lines)));
            return loadReport(loadTotalise(lines));
        }; // end this.load() 

        /**
         * Populate the model from a test report received from the api.
        **/
        this.xload = function (report) {
            $.each(report, function (i, line) {
                switch (line.type) {

                    case 'GroupStart' :
                        // add the line to the report
                        self.lines.push(line);
                        // get the index of the current line and push it on the
                        // stack so we can retrieve it at the end of the group
                        groupStack.push(self.lines.length - 1);
                        break;

                    case 'GroupEnd' :
                        // add the line to the report
                        self.lines.push(line);
                        // get the start line for the current group off the stack
                        var startLine = groupStack.pop();
                        // add the summaries from the current line to the header line
                        self.lines[startLine].count = line.count;
                        self.lines[startLine].time  = line.time;
                        break;

                    case 'ReportStart' :
                    case 'ReportEnd' :
                    case 'test' :
                        // add the line to the report
                        self.lines.push(line);
                        break;

                    default :
                        // add the line to the report
                        line.type = 'unknown';
                        self.lines.push(line);

                } // end switch
            }); // end $.each(report ...

        }; // end this.load() 

    }; // end app.classes.vmTestReport

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
    app.classes.apiRequest = function (options) {
        var self = this;
        var ajaxDefaults = {
            dataType: 'json',
            timeout:  2000,
            type:     'GET',
            url:      app.local.apiUrl
        };
        var ajax;

        this.send = function (resource, query, body) {
            if (query === undefined) {
                query = {};
            }
            $.extend(query, {api: resource});
            ajax.url = ajax.url + '?api=' + encodeURI(resource);
            $.ajax(ajax);
        };
        
        // constructor
        if (options === undefined) {
            ajax = $.extend({}, ajaxDefaults);
        } else {
            ajax = $.extend({}, ajaxDefaults, options);
        }

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

    // send a request
    var options = {
        success: function (data, textStatus, jqXHR) {
            $('#panelFiles .panel-body').text(data);
        }
    };
    var request = new app.classes.apiRequest(options);
    request.send('file/test/list');

    var options = {
        success: function (data, textStatus, jqXHR) {
            if (data.status === 'ok') {
                var report = new app.classes.vmTestReport;
                app.panels.body('panelResults').html(report.load(data.testResult));
                // ko.applyBindings(app.viewModels.testReport, app.panels.body('panelResults')[0]);
            }
        }
    };
    var request = new app.classes.apiRequest(options);
    request.send('run/23');

});
