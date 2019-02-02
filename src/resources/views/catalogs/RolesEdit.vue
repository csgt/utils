<template>
    <div class="card">
        <div v-if="loading" class="text-center">
            <div class="card-body">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
        <template v-else>
            <div class="card-body">
                <template>
                    <div class="row">
                        <div class="form-group col-sm-12">
                            <label for="nombre">Nombre</label>
                            <input name="nombre" type="text" class="form-control" v-model="data.role.name">
                        </div>
                        <div class="form-group col-sm-12">
                            <label for="descripcion">Descripción</label>
                            <input name="descripcion" type="text" class="form-control" v-model="data.role.description">
                        </div>
                    </div>
                    <label>Permisos</label>
                    <div class="row">
                        <div v-for="m in data.modules" class="col-sm-4">
                            <catalogs-rolemodule :module="m"/>
                        </div>
                    </div>
                </template>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary" @click="save" :disabled="saving">Guardar</button>
            </div>
        </template>
    </div>
</template>
<script>
    import axios from 'axios';
    export default {
        data() {
            return {
                data: {
                    role: null,
                    modules: [],
                },
                loading : true,
                saving: false
            }
        },
        props: ['id'],
        mounted() {
            axios.get('/catalogs/roles/' + this.id + '/detail')
            .then(response => {
                this.loading = false
                this.data = response.data
            })
            .catch((e) => {
                this.loading = false
                alert(e);
            });
        },
        methods: {
            save() {
                this.saving = true
                if (this.id != 0) {
                    axios.patch('/catalogs/roles/' + this.id, this.data)
                        .then((response)=> {
                            window.location = '/catalogs/roles'
                        })
                        .catch((error) => {
                            this.saving = false
                            alert(error.response.data.message)
                        })
                }
                else {
                   axios.post('/catalogs/roles', this.data)
                        .then((response)=> {
                            window.location = '/catalogs/roles'
                        })
                        .catch((error) => {
                            this.saving = false
                            alert(error.response.data.message)
                        })
                }


            },
        }
    }
</script>
