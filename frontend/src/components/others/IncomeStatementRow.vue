<template>
    <tr>
        <td :style="{ paddingLeft: `${depth * 30}px` }" class="px-4 py-2 border border-gray-200">
            {{ account.account_name || 'No Account Name' }}
        </td>
        <td class="px-4 py-2 border border-gray-200 text-right">
            {{ account.total_balance }}
        </td>
    </tr>

    <!-- Loop untuk nested children -->
    <template v-if="account.children && account.children.length > 0">
        <IncomeStatementRow 
            v-for="(child, childIndex) in account.children" 
            :key="childIndex"
            :account="child"
            :depth="depth + 1"
        />
    </template>
</template>

<script>
export default {
    name: 'IncomeStatementRow',
    props: {
        account: Object,
        depth: {
            type: Number,
            default: 1
        }
    },
}
</script>
