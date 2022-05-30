<template>
    <div class="card">
        <div v-if="loading" class="text-center">
            <div class="card-body">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
        <template v-else>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group mb-2">
                            <label for="nombre">Nombre</label>
                            <input
                                name="nombre"
                                type="text"
                                :class="{
                                    'form-control': true,
                                    'is-invalid': validationErrors['user.name'],
                                }"
                                v-model="data.user.name"
                            />
                            <div v-if="validationErrors['user.name']" class="invalid-feedback">
                                {{ validationErrors["user.name"][0] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group mb-2">
                            <label for="email">Email</label>
                            <input
                                name="email"
                                type="email"
                                autocomplete="off"
                                :class="{
                                    'form-control': true,
                                    'is-invalid': validationErrors['user.email'],
                                }"
                                v-model="data.user.email"
                            />
                            <div v-if="validationErrors['user.email']" class="invalid-feedback">
                                {{ validationErrors["user.email"][0] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group mb-2">
                            <label for="roles">Roles</label>
                            <selectize
                                name="roles"
                                :class="{
                                    'form-control': true,
                                    'is-invalid': validationErrors['user.role_ids'],
                                }"
                                v-model="data.user.role_ids"
                                multiple
                            >
                                <option v-for="role in data.roles" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </selectize>
                            <div v-if="validationErrors['user.role_ids']" class="invalid-feedback">
                                {{ validationErrors["user.role_ids"][0] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group mb-2">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="active"
                                    v-model="data.user.active"
                                />
                                <label class="form-check-label" for="active">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" v-if="id != 0">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="active"
                                    v-model="data.changePassword"
                                />
                                <label class="form-check-label" for="active">Cambiar password</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" v-if="data.changePassword || id == 0">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                name="password"
                                type="password"
                                autocomplete="off"
                                :class="{
                                    'form-control': true,
                                    'is-invalid': validationErrors['user.password'],
                                }"
                                v-model="data.user.password"
                            />
                            <div v-if="validationErrors['user.password']" class="invalid-feedback">
                                {{ validationErrors["user.password"][0] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="password_confirmation">Repetir Password</label>
                            <input
                                name="password_confirmation"
                                type="password"
                                autocomplete="off"
                                class="form-control"
                                v-model="data.user.password_confirmation"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary" @click="save" :disabled="saving">Guardar</button>
            </div>
        </template>
    </div>
</template>

<script>
import axios from "axios";
import Selectize from "vue2-selectize";

export default {
    data() {
        return {
            data: {
                user: null,
                roles: [],
                changePassword: false,
            },
            loading: true,
            saving: false,
            validationErrors: {
                user: null,
            },
        };
    },
    props: ["id", "path"],
    mounted() {
        axios
            .get(this.path + "/" + this.id + "/detail")
            .then((response) => {
                this.loading = false;
                this.data = response.data;
            })
            .catch((e) => {
                this.loading = false;
                alert(e);
            });
    },
    methods: {
        save() {
            this.saving = true;
            if (this.id != 0) {
                axios
                    .patch(this.path + "/" + this.id, this.data)
                    .then((response) => {
                        window.location = this.path;
                    })
                    .catch((error) => {
                        this.handleError(error);
                    });
            } else {
                axios
                    .post(this.path, this.data)
                    .then((response) => {
                        window.location = this.path;
                    })
                    .catch((error) => {
                        this.handleError(error);
                    });
            }
        },
        handleError(error) {
            this.saving = false;

            if (error.response.status == 422) {
                this.validationErrors = error.response.data.errors;
            } else {
                alert(error.response.data.message);
            }
        },
    },
    components: {
        Selectize,
    },
};
</script>
