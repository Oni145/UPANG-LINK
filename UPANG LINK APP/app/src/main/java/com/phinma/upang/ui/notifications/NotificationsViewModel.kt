package com.phinma.upang.ui.notifications

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.api.NotificationApi
import com.phinma.upang.data.model.Notification
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val notificationApi: NotificationApi
) : ViewModel() {

    private val _notificationsState = MutableLiveData<NotificationsState>()
    val notificationsState: LiveData<NotificationsState> = _notificationsState

    init {
        refreshNotifications()
    }

    fun refreshNotifications() {
        viewModelScope.launch {
            _notificationsState.value = NotificationsState.Loading
            try {
                val response = notificationApi.getNotifications()
                _notificationsState.value = NotificationsState.Success(response.data ?: emptyList())
            } catch (e: Exception) {
                _notificationsState.value = NotificationsState.Error(e.message ?: "Failed to load notifications")
            }
        }
    }

    fun markAllAsRead() {
        viewModelScope.launch {
            try {
                notificationApi.markAllAsRead()
                refreshNotifications()
            } catch (e: Exception) {
                _notificationsState.value = NotificationsState.Error(e.message ?: "Failed to mark notifications as read")
            }
        }
    }

    sealed class NotificationsState {
        object Loading : NotificationsState()
        data class Success(val notifications: List<Notification>) : NotificationsState()
        data class Error(val message: String) : NotificationsState()
    }
} 