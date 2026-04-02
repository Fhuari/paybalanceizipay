<script setup>
import { reactive, ref, onMounted } from 'vue'
import { message } from 'ant-design-vue'

const form = reactive({
  title_en: '',
  title_es: '',
  description_en: '',
  description_es: '',
  price: 0,
  price_type: 'person',
  active: true,
})

const items = ref([])
const loading = ref(true)
const saving = ref(false)
const editingId = ref(null)

const apiBase = window.PEARPAY_ADMIN?.rest_url || ''
const nonce = window.PEARPAY_ADMIN?.nonce || ''

const loadItems = async () => {
  loading.value = true
  try {
    const res = await fetch(`${apiBase}pearpay/v1/options`, {
      headers: { 'X-WP-Nonce': nonce },
    })
    if (!res.ok) throw new Error('Failed to load items')
    const data = await res.json()
    items.value = data.items || []
  } catch (err) {
    message.error('No se pudieron cargar las opciones.')
  } finally {
    loading.value = false
  }
}

const createItem = async () => {
  saving.value = true
  try {
    const isEditing = editingId.value !== null
    const url = isEditing
      ? `${apiBase}pearpay/v1/options/${editingId.value}`
      : `${apiBase}pearpay/v1/options`
    const res = await fetch(url, {
      method: isEditing ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(form),
    })
    if (!res.ok) throw new Error('Failed to create item')
    const data = await res.json()
    if (isEditing) {
      items.value = items.value.map((item) => (item.id === data.item.id ? data.item : item))
    } else {
      items.value = [data.item, ...items.value]
    }
    form.title_en = ''
    form.title_es = ''
    form.description_en = ''
    form.description_es = ''
    form.price = 0
    form.price_type = 'person'
    form.active = true
    editingId.value = null
    message.success(isEditing ? 'Opción actualizada.' : 'Opción creada.')
  } catch (err) {
    message.error('No se pudo guardar la opción.')
  } finally {
    saving.value = false
  }
}

const deleteItem = async (id) => {
  try {
    const res = await fetch(`${apiBase}pearpay/v1/options/${id}`, {
      method: 'DELETE',
      headers: { 'X-WP-Nonce': nonce },
    })
    if (!res.ok) throw new Error('Failed to delete')
    items.value = items.value.filter((item) => item.id !== id)
    message.success('Opción eliminada.')
  } catch (err) {
    message.error('No se pudo eliminar la opción.')
  }
}

onMounted(loadItems)

const startEdit = (record) => {
  editingId.value = record.id
  form.title_en = record.title_en || ''
  form.title_es = record.title_es || ''
  form.description_en = record.description_en || ''
  form.description_es = record.description_es || ''
  form.price = Number(record.price || 0)
  form.price_type = record.price_type || 'person'
  form.active = record.active == 1
}

const cancelEdit = () => {
  editingId.value = null
  form.title_en = ''
  form.title_es = ''
  form.description_en = ''
  form.description_es = ''
  form.price = 0
  form.price_type = 'person'
  form.active = true
}
</script>

<template>
  <a-page-header title="Create" sub-title="Crea opciones de pago" />

  <a-card style="margin-bottom: 24px;">
    <a-form layout="vertical">
      <a-row :gutter="[16, 16]">
        <a-col :span="12">
          <a-form-item label="Precio">
            <a-input-number v-model:value="form.price" :min="0" :step="1" style="width: 100%;" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Precio por">
            <a-select v-model:value="form.price_type">
              <a-select-option value="person">Persona</a-select-option>
              <a-select-option value="group">Grupo</a-select-option>
            </a-select>
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Título (EN)">
            <a-input v-model:value="form.title_en" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Título (ES)">
            <a-input v-model:value="form.title_es" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Descripción (EN)">
            <a-input v-model:value="form.description_en" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Descripción (ES)">
            <a-input v-model:value="form.description_es" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Activo">
            <a-switch v-model:checked="form.active" />
          </a-form-item>
        </a-col>
      </a-row>
      <a-space>
        <a-button type="primary" :loading="saving" @click="createItem">
          {{ editingId ? 'Actualizar opción' : 'Crear opción' }}
        </a-button>
        <a-button v-if="editingId" @click="cancelEdit">Cancelar</a-button>
      </a-space>
    </a-form>
  </a-card>

  <a-card>
    <a-table :dataSource="items" :loading="loading" rowKey="id" :pagination="{ pageSize: 5 }">
      <a-table-column title="ID" dataIndex="id" key="id" width="70" />
      <a-table-column title="Título (EN)" dataIndex="title_en" key="title_en" />
      <a-table-column title="Precio" dataIndex="price" key="price" />
      <a-table-column title="Tipo" dataIndex="price_type" key="price_type" />
      <a-table-column title="Activo" key="active">
        <template #default="{ record }">
          <span>{{ record.active == 1 ? 'Sí' : 'No' }}</span>
        </template>
      </a-table-column>
      <a-table-column title="Acciones" key="actions" width="120">
        <template #default="{ record }">
          <a-space>
            <a-button size="small" @click="startEdit(record)">Editar</a-button>
            <a-button danger size="small" @click="deleteItem(record.id)">Eliminar</a-button>
          </a-space>
        </template>
      </a-table-column>
    </a-table>
  </a-card>
</template>
