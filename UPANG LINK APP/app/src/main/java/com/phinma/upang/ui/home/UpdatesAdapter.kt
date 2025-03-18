package com.phinma.upang.ui.home

import android.content.res.ColorStateList
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.cardview.widget.CardView
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.R

/**
 * Adapter for displaying request updates in the Home screen
 */
class UpdatesAdapter : ListAdapter<Update, UpdatesAdapter.UpdateViewHolder>(UpdateDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): UpdateViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_update, parent, false)
        return UpdateViewHolder(view)
    }

    override fun onBindViewHolder(holder: UpdateViewHolder, position: Int) {
        val update = getItem(position)
        holder.bind(update)
    }

    class UpdateViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvTitle: TextView = itemView.findViewById(R.id.tvUpdateTitle)
        private val tvDescription: TextView = itemView.findViewById(R.id.tvUpdateDescription)
        private val tvDate: TextView = itemView.findViewById(R.id.tvUpdateDate)
        private val ivStatus: ImageView? = itemView.findViewById(R.id.ivStatus)
        private val cardView: CardView = itemView as CardView

        fun bind(update: Update) {
            tvTitle.text = update.title
            tvDescription.text = update.description
            tvDate.text = update.date
            
            // Set status icon and card background based on status
            ivStatus?.visibility = View.VISIBLE
            when (update.status) {
                "approved" -> {
                    ivStatus?.setImageResource(R.drawable.ic_check_circle)
                    ivStatus?.imageTintList = ColorStateList.valueOf(
                        ContextCompat.getColor(itemView.context, R.color.status_approved)
                    )
                    cardView.setCardBackgroundColor(
                        ContextCompat.getColor(itemView.context, R.color.status_completed_bg)
                    )
                }
                "in_progress" -> {
                    ivStatus?.setImageResource(R.drawable.ic_time)
                    ivStatus?.imageTintList = ColorStateList.valueOf(
                        ContextCompat.getColor(itemView.context, R.color.status_in_progress)
                    )
                    cardView.setCardBackgroundColor(
                        ContextCompat.getColor(itemView.context, R.color.status_progress_bg)
                    )
                }
                "rejected" -> {
                    ivStatus?.setImageResource(R.drawable.ic_cancel)
                    ivStatus?.imageTintList = ColorStateList.valueOf(
                        ContextCompat.getColor(itemView.context, R.color.status_rejected)
                    )
                    cardView.setCardBackgroundColor(
                        ContextCompat.getColor(itemView.context, R.color.status_rejected_bg)
                    )
                }
                else -> {
                    ivStatus?.visibility = View.GONE
                    cardView.setCardBackgroundColor(
                        ContextCompat.getColor(itemView.context, R.color.white)
                    )
                }
            }
        }
    }

    private class UpdateDiffCallback : DiffUtil.ItemCallback<Update>() {
        override fun areItemsTheSame(oldItem: Update, newItem: Update): Boolean {
            return oldItem.id == newItem.id
        }

        override fun areContentsTheSame(oldItem: Update, newItem: Update): Boolean {
            return oldItem == newItem
        }
    }
}

/**
 * Data class representing a request update item
 */
data class Update(
    val id: String,
    val title: String,
    val description: String,
    val date: String,
    val type: UpdateType = UpdateType.REQUEST_STATUS,
    val status: String
)

/**
 * Enum representing update types
 */
enum class UpdateType {
    REQUEST_STATUS  // Updates about request status changes
} 