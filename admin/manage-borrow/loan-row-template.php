<?php
// loan-row-template.php

// Pastikan variabel yang dibutuhkan tersedia
if (!isset($currentDateTime)) {
    $currentDateTime = "2025-01-27 07:44:49"; // Default value
}

$today = new DateTime($currentDateTime);
$dueDate = new DateTime($loan['due_date']);
$isOverdue = $today > $dueDate && $loan['status'] != 'returned';

// Set status berdasarkan kondisi
$status = $loan['status'];
if ($isOverdue) {
    $status = 'overdue';
}
?>

<tr class="hover:bg-gray-50 transition-colors duration-200">
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-10 w-10">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-user text-blue-500"></i>
                </div>
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">
                    <?= htmlspecialchars($loan['borrower_name'] ?? 'N/A') ?>
                </div>
                <div class="text-sm text-gray-500">
                    <?= htmlspecialchars($loan['borrower_email'] ?? 'N/A') ?>
                </div>
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900"><?= htmlspecialchars($loan['book_title'] ?? 'N/A') ?></div>
        <div class="text-sm text-gray-500"><?= htmlspecialchars($loan['book_isbn'] ?? 'N/A') ?></div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        <?= date('d M Y', strtotime($loan['loan_date'])) ?>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        <span class="<?= $isOverdue ? 'text-red-600 font-semibold' : '' ?>">
            <?= date('d M Y', strtotime($loan['due_date'])) ?>
        </span>
        <?php if ($isOverdue): ?>
            <div class="text-xs text-red-500">
                <?php
                $interval = $today->diff($dueDate);
                echo "(Terlambat {$interval->days} hari)";
                ?>
            </div>
        <?php endif; ?>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php
                                                                                    switch ($status) {
                                                                                        case 'active':
                                                                                            echo 'bg-green-100 text-green-800';
                                                                                            break;
                                                                                        case 'overdue':
                                                                                            echo 'bg-red-100 text-red-800';
                                                                                            break;
                                                                                        case 'returned':
                                                                                            echo 'bg-gray-100 text-gray-800';
                                                                                            break;
                                                                                        default:
                                                                                            echo 'bg-yellow-100 text-yellow-800';
                                                                                    }
                                                                                    ?>">
            <?= ucfirst(htmlspecialchars($status)) ?>
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm">
        <?php if (isset($loan['fine_amount']) && $loan['fine_amount'] > 0): ?>
            <span class="text-red-600 font-semibold">
                Rp <?= number_format($loan['fine_amount'], 0, ',', '.') ?>
            </span>
        <?php else: ?>
            <span class="text-gray-500">-</span>
        <?php endif; ?>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <div class="flex justify-end space-x-2">
            <a href="view-loan.php?id=<?= $loan['id'] ?>"
                class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-all duration-300">
                <i class="fas fa-eye"></i>
                <span class="hidden sm:inline ml-1">Detail</span>
            </a>

            <?php if ($status === 'active' || $status === 'overdue'): ?>
                <a href="return-loan.php?id=<?= $loan['id'] ?>"
                    class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-all duration-300">
                    <i class="fas fa-undo"></i>
                    <span class="hidden sm:inline ml-1">Kembalikan</span>
                </a>

                <a href="edit-loan.php?id=<?= $loan['id'] ?>"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg transition-all duration-300">
                    <i class="fas fa-edit"></i>
                    <span class="hidden sm:inline ml-1">Edit</span>
                </a>
            <?php endif; ?>
        </div>
    </td>
</tr>