<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum RpcMethod: string
{
    case SYSTEM_LIST = 'system.listMethods';
    case SYSTEM_SIGNATURE = 'system.methodSignature';
    case SYSTEM_HELP = 'system.methodHelp';
    case SYSTEM_MULTICALL = 'system.multicall';

    case AUTHENTICATE = 'Authenticate';
    case CHANGE_AUTH_PASSWORD = 'ChangeAuthPassword';
    case ENABLE_CALLBACKS = 'EnableCallbacks';

    // Votes
    case CALL_VOTE = 'CallVote';
    case CALL_VOTE_EX = 'CallVoteEx';
    case INTERNAL_CALL_VOTE = 'InternalCallVote';
    case CANCEL_VOTE = 'CancelVote';
    case GET_CURRENT_CALL_VOTE = 'GetCurrentCallVote';
    case SET_CALL_VOTE_TIMEOUT = 'SetCallVoteTimeOut';
    case GET_CALL_VOTE_TIMEOUT = 'GetCallVoteTimeOut';
    case SET_CALL_VOTE_RATIO = 'SetCallVoteRatio';
    case GET_CALL_VOTE_RATIO = 'GetCallVoteRatio';
    case SET_CALL_VOTE_RATIOS = 'SetCallVoteRatios';
    case GET_CALL_VOTE_RATIOS = 'GetCallVoteRatios';

    // Chat
    case CHAT_SEND_SERVER_MESSAGE = 'ChatSendServerMessage';
    case CHAT_SEND_SERVER_MESSAGE_TO_LANGUAGE = 'ChatSendServerMessageToLanguage';
    case CHAT_SEND_SERVER_MESSAGE_TO_ID = 'ChatSendServerMessageToId';
    case CHAT_SEND_SERVER_MESSAGE_TO_LOGIN = 'ChatSendServerMessageToLogin';
    case CHAT_SEND = 'ChatSend';
    case CHAT_SEND_TO_LANGUAGE = 'ChatSendToLanguage';
    case CHAT_SEND_TO_LOGIN = 'ChatSendToLogin';
    case CHAT_SEND_TO_ID = 'ChatSendToId';
    case GET_CHAT_LINES = 'GetChatLines';
    case CHAT_ENABLE_MANUAL_ROUTING = 'ChatEnableManualRouting';
    case CHAT_FORWARD_TO_LOGIN = 'ChatForwardToLogin';

    // Notices
    case SEND_NOTICE = 'SendNotice';
    case SEND_NOTICE_TO_ID = 'SendNoticeToId';
    case SEND_NOTICE_TO_LOGIN = 'SendNoticeToLogin';

    // Manialinks
    case SEND_DISPLAY_MANIALINK_PAGE = 'SendDisplayManialinkPage';
    case SEND_DISPLAY_MANIALINK_PAGE_TO_ID = 'SendDisplayManialinkPageToId';
    case SEND_DISPLAY_MANIALINK_PAGE_TO_LOGIN = 'SendDisplayManialinkPageToLogin';
    case SEND_HIDE_MANIALINK_PAGE = 'SendHideManialinkPage';
    case SEND_HIDE_MANIALINK_PAGE_TO_ID = 'SendHideManialinkPageToId';
    case GET_MANIALINK_PAGE_ANSWERS = 'GetManialinkPageAnswers';

    // Players
    case KICK = 'Kick';
    case KICK_ID = 'KickId';
    case BAN = 'Ban';
    case BAN_AND_BLACKLIST = 'BanAndBlackList';
    case BAN_ID = 'BanId';
    case UNBAN = 'UnBan';
    case CLEAN_BAN_LIST = 'CleanBanList';
    case GET_BAN_LIST = 'GetBanList';
    case GET_DETAILED_PLAYER_INFO = "GetDetailedPlayerInfo";
    case GET_PLAYER_LIST = 'GetPlayerList';

    // Blacklist
    case BLACKLIST = 'BlackList';
    case BLACKLIST_ID = 'BlackListId';
    case UNBLACKLIST = 'UnBlackList';
    case CLEAN_BLACKLIST = 'CleanBlackList';
    case GET_BLACKLIST = 'GetBlackList';
    case LOAD_BLACKLIST = 'LoadBlackList';
    case SAVE_BLACKLIST = 'SaveBlackList';

    // Guest
    case ADD_GUEST = 'AddGuest';
    case ADD_GUEST_ID = 'AddGuestId';
    case REMOVE_GUEST = 'RemoveGuest';
    case REMOVE_GUEST_ID = 'RemoveGuestId';
    case CLEAN_GUEST_LIST = 'CleanGuestList';
    case GET_GUEST_LIST = 'GetGuestList';
    case LOAD_GUEST_LIST = 'LoadGuestList';
    case SAVE_GUEST_LIST = 'SaveGuestList';

    // Buddy Notification
    case SET_BUDDY_NOTIFICATION = 'SetBuddyNotification';
    case GET_BUDDY_NOTIFICATION = 'GetBuddyNotification';

    // Files & Tunnel
    case WRITE_FILE = 'WriteFile';
    case TUNNEL_SEND_DATA_TO_ID = 'TunnelSendDataToId';
    case TUNNEL_SEND_DATA_TO_LOGIN = 'TunnelSendDataToLogin';

    // Ignore / Unignore
    case IGNORE = 'Ignore';
    case IGNORE_ID = 'IgnoreId';
    case UNIGNORE = 'UnIgnore';
    case UNIGNORE_ID = 'UnIgnoreId';
    case CLEAN_IGNORE_LIST = 'CleanIgnoreList';
    case GET_IGNORE_LIST = 'GetIgnoreList';

    // Economic
    case PAY = 'Pay';
    case SEND_BILL = 'SendBill';
    case GET_BILL_STATE = 'GetBillState';

    // System / Server Info
    case GET_STATUS = 'GetStatus';
    case GET_VERSION = 'GetVersion';
    case GET_WARM_UP = 'GetWarmUp';
    case GET_SYSTEM_INFO = 'GetSystemInfo';
    case SET_CONNECTION_RATES = 'SetConnectionRates';
    case SET_SERVER_NAME = 'SetServerName';
    case GET_SERVER_NAME = 'GetServerName';
    case SET_SERVER_COMMENT = 'SetServerComment';
    case GET_SERVER_COMMENT = 'GetServerComment';
    case GET_SERVER_PACK_MASK = 'GetServerPackMask';
    case GET_SERVER_OPTIONS = 'GetServerOptions';
    case GET_LADDER_SERVER_LIMITS = 'GetLadderServerLimits';
    case GET_CURRENT_GAME_INFO = 'GetCurrentGameInfo';

    // Password & Access
    case SET_SERVER_PASSWORD = 'SetServerPassword';
    case GET_SERVER_PASSWORD = 'GetServerPassword';
    case SET_SERVER_PASSWORD_SPECTATOR = 'SetServerPasswordForSpectator';
    case GET_SERVER_PASSWORD_SPECTATOR = 'GetServerPasswordForSpectator';

    // Players Limits
    case SET_MAX_PLAYERS = 'SetMaxPlayers';
    case GET_MAX_PLAYERS = 'GetMaxPlayers';
    case SET_MAX_SPECTATORS = 'SetMaxSpectators';
    case GET_MAX_SPECTATORS = 'GetMaxSpectators';

    // P2P
    case ENABLE_P2P_UPLOAD = 'EnableP2PUpload';
    case IS_P2P_UPLOAD = 'IsP2PUpload';
    case ENABLE_P2P_DOWNLOAD = 'EnableP2PDownload';
    case IS_P2P_DOWNLOAD = 'IsP2PDownload';

    // MAPS
    case NEXT_CHALLENGE = 'NextChallenge';
    case GET_CURRENT_CHALLENGE_INFO = 'GetCurrentChallengeInfo';
    case GET_CHALLENGE_LIST = 'GetChallengeList';
    case GET_NEXT_CHALLENGE_INDEX = 'GetNextChallengeIndex';
}
