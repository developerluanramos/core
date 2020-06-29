import SessionsApi from './api/annotationSessions';
import VolumesApi from './api/volumes';
import {EditorMixin} from './import';
import {handleErrorResponse} from './import';
import {LoaderMixin} from './import';
import {Typeahead} from './import';

/**
 * The panel for editing annotation sessions
 */
let emptySession = function () {
    return {
        name: null,
        description: null,
        starts_at_iso8601: null,
        starts_at: null,
        ends_at_iso8601: null,
        ends_at: null,
        hide_other_users_annotations: false,
        hide_own_annotations: false,
        users: [],
    };
};

let listItem = {
    props: ['session', 'editing', 'editId'],
    computed: {
        title() {
            return this.editing ? 'Edit this annotation session' : this.session.name;
        },
        active() {
            let now = new Date();

            return this.session.starts_at_iso8601 < now && this.session.ends_at_iso8601 >= now;
        },
        currentlyEdited() {
            return this.session.id === this.editId;
        },
        classObject() {
            return {
                'session--active': this.active,
                'list-group-item-info': this.currentlyEdited,
            };
        },
    },
    methods: {
        edit() {
            if (!this.editing || this.currentlyEdited) return;
            this.$emit('edit', this.session);
        },
    },
};

let userTag = {
    props: ['user'],
    computed: {
        name() {
            return this.user.firstname + ' ' + this.user.lastname;
        },
        title() {
            return 'Remove ' + this.name;
        },
    },
    methods: {
        remove() {
            this.$emit('remove', this.user);
        },
    },
};

export default {
    mixins: [
        LoaderMixin,
        EditorMixin,
    ],
    data: {
        volumeId: null,
        sessions: null,
        editedSession: emptySession(),
        users: [],
        errors: {},
        typeaheadTemplate: '<span v-text="item.name"></span><br><small v-text="item.affiliation"></small>',
    },
    components: {
        typeahead: Typeahead,
        listItem: listItem,
        userTag: userTag,
        datepicker: VueStrap.datepicker,
    },
    computed: {
        classObject() {
            return {
                'panel-warning panel--editing': this.editing,
            };
        },
        hasSessions() {
            return this.sessions.length > 0;
        },
        hasNewSession() {
            return this.editedSession.id === undefined;
        },
        availableUsers() {
            let members = this.editedSession.users.map((user) => user.id);

            return this.users.filter((user) => members.indexOf(user.id) === -1);
        },
        orderedSessions() {
            return this.sessions.sort(function (a, b) {
                return b.starts_at_iso8601.getTime() - a.starts_at_iso8601.getTime();
            });
        },
    },
    methods: {
        clone(thing) {
            return JSON.parse(JSON.stringify(thing));
        },
        submit(force) {
            if (this.loading) return;

            this.startLoading();
            let session = this.editedSession;
            if (this.hasNewSession) {
                SessionsApi.save({volume_id: this.volumeId}, this.packSession(session))
                    .then(this.sessionSaved)
                    .catch(this.handleErrorResponse)
                    .finally(this.finishLoading);
            } else {
                let params = {
                    id: session.id,
                };

                if (force === true) {
                    params.force = 1;
                }

                SessionsApi.update(params, this.packSession(session))
                    .then(() => this.sessionUpdated(session))
                    .catch(this.handleConfirm('Use the Force and update the annotation session?', this.submit))
                    .finally(this.finishLoading);
            }
        },
        sessionUpdated(session) {
            for (let i = this.sessions.length - 1; i >= 0; i--) {
                if (this.sessions[i].id === session.id) {
                    this.sessions.splice(i, 1, session);
                    this.clearEditedSession();
                }
            }
        },
        sessionSaved(response) {
            this.sessions.push(this.parseSession(response.data));
            this.clearEditedSession();
        },
        packSession(session) {
            // The API endpoint expects an array of user IDs and not user objects.
            // Deep copy the session first so the original object remains the same.
            session = this.clone(session);
            session.users = session.users.map((user) => user.id);
            // Replace the human readable dates with the exact dates for storage.
            session.starts_at = session.starts_at_iso8601;
            session.ends_at = session.ends_at_iso8601;
            // Remove these to minimize the request payload.
            delete session.starts_at_iso8601;
            delete session.ends_at_iso8601;

            return session;
        },
        handleConfirm(message, callback) {
            return (response) => {
                if (response.status === 400) {
                    this.finishLoading();
                    if (confirm(response.data.message + ' ' + message)) {
                        callback(true);
                    }
                } else {
                    this.handleErrorResponse(response);
                }
            };
        },
        handleErrorResponse(response) {
            if (response.status === 422) {
                this.errors = response.data.errors;
            } else {
                handleErrorResponse(response);
            }
        },
        hasError(name) {
            return this.errors.hasOwnProperty(name);
        },
        getError(name) {
            return this.errors[name].join(' ');
        },
        editSession(session) {
            this.editedSession = this.clone(session);
        },
        deleteSession(force) {
            if (this.loading || this.hasNewSession) return;

            if (force === true || confirm(`Are you sure you want to delete the annotation session '${this.editedSession.name}'?`)) {
                this.startLoading();
                let id = this.editedSession.id;
                let params = {
                    id: id,
                };

                if (force === true) {
                    params.force = 1;
                }

                SessionsApi.delete(params)
                    .then(() => this.sessionDeleted(id))
                    .catch(this.handleConfirm('Use the Force and delete the annotation session?', this.deleteSession))
                    .finally(this.finishLoading);
            }
        },
        sessionDeleted(id) {
            for (let i = this.sessions.length - 1; i >= 0; i--) {
                if (this.sessions[i].id === id) {
                    this.sessions.splice(i, 1);
                    this.clearEditedSession();
                    return;
                }
            }
        },
        clearEditedSession() {
            this.editedSession = emptySession();
        },
        loadUsers() {
            VolumesApi.queryUsers({id: this.volumeId})
                .then(this.usersLoaded, handleErrorResponse);
        },
        usersLoaded(response) {
            // Assemble full username that can be used for searching in the typeahead.
            response.data.forEach(function (user) {
                user.name = user.firstname + ' ' + user.lastname;
            });
            Vue.set(this, 'users', response.data);
        },
        selectUser(user) {
            this.editedSession.users.push(user);
        },
        removeUser(user) {
            for (let i = this.editedSession.users.length - 1; i >= 0; i--) {
                if (this.editedSession.users[i].id === user.id) {
                    this.editedSession.users.splice(i, 1);
                }
            }
        },
        // convert a date object to yyyy-MM-dd
        stringifyDate(d) {
            let month = d.getMonth() + 1;
            month = month < 10 ? '0' + month : month;
            return d.getFullYear() + '-' + month + '-' + d.getDate();
        },
        // convert a yyyy-MM-dd string to a date object
        parseDate(s) {
            if (!s) return;
            s = s.split('-');
            return new Date(s[0], s[1] - 1, s[2]);
        },
        // convert session date strings to date objects
        parseSession(session) {
            let date = new Date(session.starts_at_iso8601);
            session.starts_at_iso8601 = date;
            session.starts_at = this.stringifyDate(date);
            date = new Date(session.ends_at_iso8601);
            session.ends_at_iso8601 = date;
            session.ends_at = this.stringifyDate(date);

            return session;
        },
        setStartsAt(date) {
            this.editedSession.starts_at_iso8601 = this.parseDate(date);
            this.editedSession.starts_at = date;
        },
        setEndsAt(date) {
            this.editedSession.ends_at_iso8601 = this.parseDate(date);
            this.editedSession.ends_at = date;
        },
    },
    watch: {
        editedSession() {
            this.errors = {};
        },
        loading(loading) {
            if (loading) {
                this.errors = {};
            }
        },
    },
    created() {
        this.volumeId = biigle.$require('volumes.id');
        this.sessions = biigle.$require('volumes.annotationSessions')
            .map(this.parseSession);
        this.$once('editing.start', this.loadUsers);
    },
};
